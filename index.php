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
 * List all Certification Manager activities in a course.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url('/mod/certmanager/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

if (!$certmanagers = get_all_instances_in_course('certmanager', $course)) {
    notice(
        'There are no Certification Manager activities in this course.',
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = ['Name', 'Description', 'Validity (days)', 'Certificates'];
$table->align = ['left', 'left', 'center', 'center'];

foreach ($certmanagers as $certmanager) {
    $link = html_writer::link(
        new moodle_url('/mod/certmanager/view.php', ['id' => $certmanager->coursemodule]),
        format_string($certmanager->name),
        ['class' => $certmanager->visible ? '' : 'dimmed']
    );

    $certstatus = $certmanager->enablecertificate ? 'Enabled' : 'Disabled';

    $table->data[] = [
        $link,
        format_module_intro('certmanager', $certmanager, $certmanager->coursemodule),
        $certmanager->validityperiod,
        $certstatus,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
