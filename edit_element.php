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
$form->display();
echo $OUTPUT->footer();
