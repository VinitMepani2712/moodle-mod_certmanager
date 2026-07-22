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
 * AJAX endpoint: save element position/size from the drag-drop designer.
 *
 * Expects: cmid, eid, posx, posy, width, height (in mm).
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\element\manager;

$cmid   = required_param('cmid', PARAM_INT);
$eid    = required_param('eid', PARAM_INT);
$posx   = required_param('posx', PARAM_FLOAT);
$posy   = required_param('posy', PARAM_FLOAT);
$width  = optional_param('width', -1, PARAM_FLOAT);
$height = optional_param('height', -1, PARAM_FLOAT);

$cm = get_coursemodule_from_id('certmanager', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

$el = manager::get($eid);
if (!$el || $el->get_record()->certmanagerid != $certmanager->id) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['ok' => false, 'error' => 'Element not found']);
    exit;
}

$fields = ['posx' => $posx, 'posy' => $posy];
if ($width >= 0) {
    $fields['width'] = $width;
}
if ($height >= 0) {
    $fields['height'] = $height;
}

manager::update_geometry($eid, $fields);

echo json_encode(['ok' => true]);
