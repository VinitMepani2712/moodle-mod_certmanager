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
 * AJAX endpoint: reorder elements after drag-drop in the side panel.
 *
 * Receives a list of element IDs in new sort order, updates DB.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$order = required_param('order', PARAM_RAW); // JSON array of element IDs.

$cm = get_coursemodule_from_id('certmanager', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

$ids = json_decode($order, true);
if (!is_array($ids)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['ok' => false, 'error' => 'Invalid order array']);
    exit;
}

$now = time();
$sortorder = 0;
foreach ($ids as $eid) {
    $eid = (int)$eid;
    $el = $DB->get_record('certmanager_elements', ['id' => $eid, 'certmanagerid' => $certmanager->id]);
    if (!$el) {
        continue;
    }

    // Background always stays at sortorder 0; everything else increments.
    if ($el->element === 'background') {
        $DB->update_record('certmanager_elements', (object)[
            'id' => $eid, 'sortorder' => 0, 'timemodified' => $now,
        ]);
    } else {
        $sortorder++;
        $DB->update_record('certmanager_elements', (object)[
            'id' => $eid, 'sortorder' => $sortorder, 'timemodified' => $now,
        ]);
    }
}

echo json_encode(['ok' => true]);
