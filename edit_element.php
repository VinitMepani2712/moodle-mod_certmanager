<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit a specific certificate element.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\element\manager;

// URL param `cmid` = course-module id (renamed from `id` in the URL to avoid collision
// with any form field also named `id`).
$cmid = required_param('cmid', PARAM_INT);
$eid  = required_param('eid', PARAM_INT);

$cm = get_coursemodule_from_id('certmanager', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

$element = manager::get($eid);
if (!$element || $element->get_record()->certmanagerid != $certmanager->id) {
    throw new \moodle_exception('invalidrecord', 'error', '', 'element');
}

$pageurl = new moodle_url('/mod/certmanager/edit_element.php', ['cmid' => $cmid, 'eid' => $eid]);
$backurl = new moodle_url('/mod/certmanager/edit.php', ['id' => $cmid]);
$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($certmanager->name) . ' - Edit element');
$PAGE->set_heading(format_string($certmanager->name));
$PAGE->set_pagelayout('admin');

// Prep draft area for any image element.
$draftitemid = file_get_submitted_draft_itemid('imagefile');
file_prepare_draft_area(
    $draftitemid,
    $context->id,
    'mod_certmanager',
    'elementfiles',
    $element->get_id(),
    ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
);

// Build initial form data.
$formdata = new \stdClass();
$formdata->font = $element->get_font();
$formdata->fontsize = $element->get_fontsize();
$formdata->colour = $element->get_colour();
$formdata->alignment = $element->get_alignment();
$formdata->posx = $element->get_posx();
$formdata->posy = $element->get_posy();
$formdata->width = $element->get_width();
$formdata->height = $element->get_height();
$formdata->imagefile = $draftitemid;
// Merge in stored type-specific data so its form fields default correctly.
foreach ($element->get_data() as $k => $v) {
    if (!isset($formdata->$k)) {
        $formdata->$k = $v;
    }
}

$form = new \mod_certmanager\form\edit_element_form($pageurl, [
    'element' => $element, 'context' => $context,
]);
$form->set_data($formdata);

if ($form->is_cancelled()) {
    redirect($backurl);
}

if ($data = $form->get_data()) {
    // Update geometry/style.
    manager::update_geometry($element->get_id(), [
        'posx' => (float)$data->posx,
        'posy' => (float)$data->posy,
        'width' => (float)$data->width,
        'height' => (float)$data->height,
        'font' => $data->font,
        'fontsize' => (int)$data->fontsize,
        'colour' => $data->colour,
        'alignment' => $data->alignment,
    ]);
    // Update element-specific data.
    $newdata = $element->extract_data_from_form($data, $context);
    manager::set_data($element->get_id(), $newdata);
    // Save any files (images).
    $element->after_save($data, $context);

    redirect($backurl, get_string('elementsaved', 'mod_certmanager'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editelementheading', 'mod_certmanager', s($element->get_name())));

// Render canvas preview and form side-by-side.
echo '<div class="certmanager-edit-container">';

// Left side: Form
echo '<div class="certmanager-edit-form">';
$form->display();
echo '</div>';

// Right side: Canvas preview
echo '<div class="certmanager-edit-preview">';
echo '<h3>' . get_string('preview', 'mod_certmanager') . '</h3>';

// Render a mini canvas showing the current element.
echo '<div id="certmanager-element-preview" class="certmanager-preview-canvas" style="width: 400px; height: 283px; background: white; border: 1px solid #ccc; position: relative; overflow: hidden;">';

// Clone the element from the main canvas for preview.
$elementhtml = '<div class="certmanager-el certmanager-el-' . $element->get_type() . '" ';
$elementhtml .= 'data-eid="' . $element->get_id() . '" ';
$elementhtml .= 'style="position: absolute; ';
$elementhtml .= 'left: ' . (($element->get_posx() / 297) * 100) . '%; ';
$elementhtml .= 'top: ' . (($element->get_posy() / 210) * 100) . '%; ';
if ($element->get_width() > 0) {
    $elementhtml .= 'width: ' . (($element->get_width() / 297) * 100) . '%; ';
}
if ($element->get_height() > 0) {
    $elementhtml .= 'height: ' . (($element->get_height() / 210) * 100) . '%; ';
}
$elementhtml .= 'font-family: ' . $element->get_font() . '; ';
$elementhtml .= 'font-size: ' . $element->get_fontsize() . 'pt; ';
$elementhtml .= 'color: ' . $element->get_colour() . '; ';
$elementhtml .= 'text-align: ' . ($element->get_alignment() === 'L' ? 'left' : ($element->get_alignment() === 'R' ? 'right' : 'center')) . '; ';
$elementhtml .= '">';

// Get element display content.
// For preview, use render_html with dummy course/certmanager.
$dummyCourse = (object)['id' => 0, 'fullname' => 'Course Name'];
$dummyCertmanager = (object)['name' => 'Certification Name'];
$elementhtml .= $element->render_html($dummyCertmanager, $dummyCourse);
$elementhtml .= '</div>';

echo $elementhtml;
echo '</div>';
echo '</div>';

echo '</div>';

// CSS for layout.
$css = <<<CSS
<style>
.certmanager-edit-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}
.certmanager-edit-form {
    flex: 1;
    min-width: 400px;
}
.certmanager-edit-preview {
    flex: 0 0 420px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 600px;
    overflow-y: auto;
}
.certmanager-edit-preview h3 {
    margin-top: 0;
    font-size: 16px;
    color: #333;
}
.certmanager-preview-canvas {
    background: white !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transform: scale(0.75);
    transform-origin: top left;
    width: 533px;
    height: 377px;
}
@media (max-width: 1200px) {
    .certmanager-edit-container {
        flex-direction: column;
    }
    .certmanager-edit-preview {
        flex: 0 0 auto;
        max-height: none;
    }
}
</style>
CSS;

echo $css;

// Initialize live preview.
$PAGE->requires->js_call_amd('mod_certmanager/element_preview_form', 'init', [$element->get_id()]);

echo $OUTPUT->footer();