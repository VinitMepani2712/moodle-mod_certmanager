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
 * Import a certificate design template (JSON) into this activity.
 *
 * Either replaces all elements or appends to them.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\element\manager;

$id = required_param('id', PARAM_INT); // Course-module id.

$cm = get_coursemodule_from_id('certmanager', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

$pageurl = new moodle_url('/mod/certmanager/import.php', ['id' => $id]);
$backurl = new moodle_url('/mod/certmanager/edit.php', ['id' => $id]);
$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($certmanager->name) . ' - Import template');
$PAGE->set_heading(format_string($certmanager->name));
$PAGE->set_pagelayout('admin');

$form = new \mod_certmanager\form\import_form($pageurl);

if ($form->is_cancelled()) {
    redirect($backurl);
}

if ($data = $form->get_data()) {
    $json = $form->get_file_content('templatefile');
    if (!$json) {
        redirect($pageurl, get_string('nofileuploaded', 'mod_certmanager'), null, \core\output\notification::NOTIFY_ERROR);
    }
    try {
        $count = manager::import_json(
            (int)$certmanager->id,
            $json,
            ($data->mode === 'replace'),
            $context
        );
        redirect(
            $backurl,
            "Imported {$count} element(s) from template",
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\Throwable $e) {
        redirect(
            $pageurl,
            'Import failed: ' . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importheading', 'mod_certmanager'));
echo html_writer::div(
    'Upload a .json template file exported from another certification activity. ' .
    'Images will need to be re-uploaded — the template only carries positions, styling, and text.',
    'alert alert-info'
);
$form->display();
echo $OUTPUT->footer();
