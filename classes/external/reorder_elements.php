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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_certmanager\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * External API: Reorder certificate elements via drag-drop in side panel.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reorder_elements extends external_api {
    /**
     * Parameter validation for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'order' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Element ID'),
                'Ordered list of element IDs'
            ),
        ]);
    }

    /**
     * Reorder elements.
     *
     * @param int $cmid Course module ID
     * @param array $order Ordered array of element IDs
     * @return array Success status
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function execute(int $cmid, array $order): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'order' => $order,
        ]);

        // Get course module and context.
        $cm = get_coursemodule_from_id('certmanager', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

        // Check login and permissions.
        require_login($course, true, $cm);
        require_capability('moodle/course:manageactivities', \context_course::instance($course->id));

        // Update sort order for each element.
        $now = time();
        $sortorder = 0;

        foreach ($params['order'] as $eid) {
            $el = $DB->get_record('certmanager_elements', [
                'id' => $eid,
                'certmanagerid' => $certmanager->id,
            ]);

            if (!$el) {
                // Skip elements that don't exist or don't belong to this certmanager.
                continue;
            }

            // Background element always stays at sortorder 0.
            if ($el->element === 'background') {
                $DB->update_record('certmanager_elements', (object) [
                    'id' => $eid,
                    'sortorder' => 0,
                    'timemodified' => $now,
                ]);
            } else {
                $sortorder++;
                $DB->update_record('certmanager_elements', (object) [
                    'id' => $eid,
                    'sortorder' => $sortorder,
                    'timemodified' => $now,
                ]);
            }
        }

        return ['ok' => true];
    }

    /**
     * Return value for execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Operation successful'),
        ]);
    }
}
