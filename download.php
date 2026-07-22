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
 * Script to download generated certificates.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$view = optional_param('view', 0, PARAM_INT);

$cm = get_coursemodule_from_id('certmanager', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/certmanager:view', context_module::instance($cm->id));

$context = context_module::instance($cm->id);

// Get the certificate file.
$file = mod_certmanager\certificate_manager::get_certificate_file($certmanager->id, $USER->id);

if (!$file) {
    // Certificate not found - try to regenerate.
    $state = $DB->get_record(
        'certmanager_state',
        ['certmanagerid' => $certmanager->id, 'userid' => $USER->id]
    );

    if ($state && $state->status == mod_certmanager\engine\state_machine::STATUS_CERTIFIED) {
        try {
            mod_certmanager\certificate_manager::generate_certificate(
                $certmanager->id,
                $USER->id,
                $state->timecertified,
                $state->timeexpires
            );
            // Try to get the file again.
            $file = mod_certmanager\certificate_manager::get_certificate_file($certmanager->id, $USER->id);
        } catch (Exception $e) {
            throw new \moodle_exception('errcertgen', 'mod_certmanager', '', null, $e->getMessage());
        }
    }

    if (!$file) {
        throw new \moodle_exception('errcertnotfound', 'mod_certmanager');
    }
}

// Verify file is a PDF.
if ($file->get_mimetype() !== 'application/pdf') {
    throw new \moodle_exception('errcertnotpdf', 'mod_certmanager');
}

// Send the file.
if ($view) {
    // View in browser - use inline display.
    send_stored_file($file, 0, 0, false);
} else {
    // Download as file - force download.
    send_stored_file($file, 86400, 0, true);
}
