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
 * Generate a preview PDF using the current user + course + a fake certificate.
 *
 * Useful for quickly checking the layout without awarding.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\certificate_generator;

$id = required_param('id', PARAM_INT); // Course-module id.

$cm = get_coursemodule_from_id('certmanager', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

global $USER;

$fakecert = new stdClass();
$fakecert->code = 'PREVIEW12345';
$fakecert->timecertified = time();
$fakecert->timeexpires = time() + (365 * 86400);

try {
    $generator = new certificate_generator();
    $pdf = $generator->generate($certmanager, $USER, $fakecert);
} catch (\Throwable $e) {
    header('Content-Type: text/plain');
    echo 'Preview generation failed: ' . $e->getMessage();
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="preview.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: no-cache, must-revalidate');
echo $pdf;
exit;
