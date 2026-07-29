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
use external_single_structure;
use external_value;
use mod_certmanager\element\manager;

/**
 * External API: Save element geometry (position and size) from certificate designer.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_element_geometry extends external_api {
    /**
     * Parameter validation for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'eid' => new external_value(PARAM_INT, 'Element ID'),
            'posx' => new external_value(PARAM_FLOAT, 'X position in mm'),
            'posy' => new external_value(PARAM_FLOAT, 'Y position in mm'),
            'width' => new external_value(PARAM_FLOAT, 'Width in mm', VALUE_OPTIONAL, -1),
            'height' => new external_value(PARAM_FLOAT, 'Height in mm', VALUE_OPTIONAL, -1),
        ]);
    }

    /**
     * Save element geometry.
     *
     * @param int $cmid Course module ID
     * @param int $eid Element ID
     * @param float $posx X position
     * @param float $posy Y position
     * @param float $width Width (optional)
     * @param float $height Height (optional)
     * @return array Success status
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function execute(
        int $cmid,
        int $eid,
        float $posx,
        float $posy,
        float $width = -1,
        float $height = -1
    ): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'eid' => $eid,
            'posx' => $posx,
            'posy' => $posy,
            'width' => $width,
            'height' => $height,
        ]);

        // Get course module and context.
        $cm = get_coursemodule_from_id('certmanager', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

        // Check login and permissions.
        require_login($course, true, $cm);
        require_capability('moodle/course:manageactivities', \context_course::instance($course->id));

        // Verify element belongs to this certmanager.
        $el = manager::get($params['eid']);
        if (!$el || $el->get_record()->certmanagerid != $certmanager->id) {
            throw new \moodle_exception('elementnotfound', 'mod_certmanager');
        }

        // Update geometry fields.
        $fields = [
            'posx' => $params['posx'],
            'posy' => $params['posy'],
        ];

        if ($params['width'] >= 0) {
            $fields['width'] = $params['width'];
        }
        if ($params['height'] >= 0) {
            $fields['height'] = $params['height'];
        }

        manager::update_geometry($params['eid'], $fields);

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
