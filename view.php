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
 * Main view page for Certification Manager activity.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\engine\state_machine;
use mod_certmanager\certificate_manager;

$id = optional_param('id', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$awarduser = optional_param('awarduser', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('certmanager', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $certmanager = $DB->get_record('certmanager', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $certmanager->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('certmanager', $certmanager->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
require_capability('mod/certmanager:view', context_module::instance($cm->id));

$isteacher = has_capability('moodle/course:manageactivities', context_course::instance($course->id));

// Handle manual award action.
if ($action === 'awardform' && $isteacher && $awarduser > 0) {
    $pageurl = new moodle_url(
        '/mod/certmanager/view.php',
        ['id' => $cm->id, 'action' => 'awardform', 'awarduser' => $awarduser]
    );

    $context = context_module::instance($cm->id);
    $awardform = new award_certificate_form($pageurl, [
        'context' => $context,
        'instance' => $certmanager,
        'cmid' => $cm->id,
        'userid' => $awarduser,
    ]);

    if ($awardform->is_cancelled()) {
        redirect(new moodle_url('/mod/certmanager/view.php', ['id' => $cm->id]));
    }

    if ($data = $awardform->get_data()) {
        // Calculate expiry based on form selection.
        $timeexpires = expiry_calculator::calculate(
            time(),
            $data->expiryoption,
            (int)$certmanager->validityperiod,
            (int)($data->customdays ?? 0),
            (int)($data->manualdate ?? 0)
        );

        if ($timeexpires === false) {
            // Fallback to default if validation fails.
            $timeexpires = (int)$certmanager->validityperiod > 0
                ? time() + ((int)$certmanager->validityperiod * 86400)
                : 0;
        }

        // Award with calculated expiry.
        state_machine::award_certification(
            $certmanager->id,
            $awarduser,
            $data->expiryoption,
            (int)($data->customdays ?? 0),
            (int)($data->manualdate ?? 0)
        );

        redirect(
            new moodle_url('/mod/certmanager/view.php', ['id' => $cm->id]),
            'Certification awarded: ' . expiry_calculator::format_expiry($timeexpires),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // Display form.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string(
        'awardcertificationfor',
        'mod_certmanager',
        fullname(\core_user::get_user($awarduser))
    ));
    $awardform->display();
    echo $OUTPUT->footer();
    exit;
}

// Quick award (backward compatible, no expiry selection).
if ($action === 'award' && $isteacher && confirm_sesskey()) {
    // Direct award using default expiry from activity settings.
    $targetuser = $awarduser > 0 ? $awarduser : $USER->id;
    state_machine::award_certification($certmanager->id, $targetuser, 'default');
    redirect(
        new moodle_url('/mod/certmanager/view.php', ['id' => $cm->id]),
        'Certification awarded successfully!',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
$PAGE->set_url('/mod/certmanager/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($certmanager->name));
$PAGE->set_heading(format_string($certmanager->name));

$state = state_machine::get_or_create_state($certmanager->id, $USER->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($certmanager->name));

if ($certmanager->intro) {
    echo format_module_intro('certmanager', $certmanager, $cm->id);
}

// Status badge.
$statusclass = 'badge-secondary';
$statustext = get_string(state_machine::get_status_string($state->status), 'mod_certmanager');

if ($state->status == state_machine::STATUS_CERTIFIED) {
    $statusclass = 'badge-success';
} else if ($state->status == state_machine::STATUS_EXPIRING) {
    $statusclass = 'badge-warning';
} else if ($state->status == state_machine::STATUS_EXPIRED || $state->status == state_machine::STATUS_LAPSED) {
    $statusclass = 'badge-danger';
}

echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', 'Certification Status', ['class' => 'card-title']);
echo html_writer::tag('span', $statustext, ['class' => "badge $statusclass p-2"]);

if ($state->timecertified > 0) {
    echo html_writer::div('Awarded: ' . userdate($state->timecertified, '%d %B %Y'), 'mt-2');
}
if ($state->timeexpires > 0) {
    echo html_writer::div('Expires: ' . userdate($state->timeexpires, '%d %B %Y'), 'mt-1');
}

echo html_writer::end_div();
echo html_writer::end_div();


// Display certificate download/view buttons for certified users.
if (!empty($certmanager->enablecertificate) && $state->status == state_machine::STATUS_CERTIFIED) {
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', 'Your Certificate', ['class' => 'card-title']);

    $file = certificate_manager::get_certificate_file($certmanager->id, $USER->id);

    if ($file && $file->get_mimetype() === 'application/pdf') {
        // Valid PDF certificate exists.
        $viewurl = new moodle_url('/mod/certmanager/download.php', ['id' => $cm->id, 'view' => 1]);
        $downloadurl = new moodle_url('/mod/certmanager/download.php', ['id' => $cm->id]);

        echo html_writer::start_div('mb-2');
        echo html_writer::link(
            $viewurl,
            html_writer::tag('i', '', ['class' => 'fa fa-eye']) . ' View Certificate',
            ['class' => 'btn btn-primary', 'target' => '_blank', 'title' => 'Open certificate in new tab']
        );
        echo '&nbsp;';
        echo html_writer::link(
            $downloadurl,
            html_writer::tag('i', '', ['class' => 'fa fa-download']) . ' Download PDF',
            ['class' => 'btn btn-secondary', 'title' => 'Download certificate as PDF']
        );
        echo html_writer::end_div();

        echo html_writer::div('Certificate file: ' . $file->get_filename(), 'small text-muted');
    } else {
        // Certificate needs to be generated.
        echo html_writer::div('Your certificate is being prepared. Please refresh the page in a moment.', 'alert alert-info');

        if ($state && $state->status == state_machine::STATUS_CERTIFIED) {
            try {
                certificate_manager::generate_certificate(
                    $certmanager->id,
                    $USER->id,
                    $state->timecertified,
                    $state->timeexpires
                );
                echo html_writer::div(
                    get_string('certgeneratedrefresh', 'mod_certmanager'),
                    'alert alert-success mt-2'
                );
            } catch (Exception $e) {
                echo html_writer::div(
                    'Error generating certificate: ' . $e->getMessage(),
                    'alert alert-danger mt-2'
                );
            }
        }
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Teacher section.
if ($isteacher) {
    echo html_writer::tag('hr', '');
    echo html_writer::tag('h4', 'Teacher Tools');

    // Design certificate button.
    echo html_writer::link(
        new moodle_url('/mod/certmanager/edit.php', ['id' => $cm->id]),
        'Design Certificate',
        ['class' => 'btn btn-primary mr-2']
    );

    // Manual award button (for testing).
    if ($state->status != state_machine::STATUS_CERTIFIED) {
        $awardurl = new moodle_url('/mod/certmanager/view.php', [
            'id' => $cm->id,
            'action' => 'award',
            'awarduser' => $USER->id,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link(
            $awardurl,
            'Award Certificate to Me (Test)',
            ['class' => 'btn btn-warning ml-2']
        );
    }

    // Show enrolled users and their status.
    echo html_writer::tag('h5', 'Enrolled Users', ['class' => 'mt-4']);
    $enrolled = get_enrolled_users(context_course::instance($course->id));

    $table = new html_table();
    $table->head = ['Name', 'Email', 'Status', 'Action'];
    $table->attributes = ['class' => 'table table-striped'];

    foreach ($enrolled as $user) {
        $userstate = state_machine::get_or_create_state($certmanager->id, $user->id);
        $userstatus = get_string(state_machine::get_status_string($userstate->status), 'mod_certmanager');

        $actionhtml = '';
        if ($userstate->status != state_machine::STATUS_CERTIFIED) {
            $awardurl = new moodle_url('/mod/certmanager/view.php', [
                'id' => $cm->id,
                'action' => 'award',
                'awarduser' => $user->id,
                'sesskey' => sesskey(),
            ]);
            $actionhtml = html_writer::link($awardurl, 'Award', ['class' => 'btn btn-sm btn-success']);
        } else {
            $actionhtml = html_writer::tag('span', 'Certified', ['class' => 'badge badge-success']);
        }

        $table->data[] = [
            fullname($user),
            $user->email,
            $userstatus,
            $actionhtml,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
