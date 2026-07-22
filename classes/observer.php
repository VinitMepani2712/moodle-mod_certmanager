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
 * Event observer for course module completion
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager;

use mod_certmanager\engine\state_machine;

/**
 * Event observers for the certmanager module.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * React to a course module completion event.
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function course_module_completed(\core\event\course_module_completion_updated $event) {
        global $DB;

        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        if (!$userid || !$courseid) {
            return;
        }

        $activities = $DB->get_records('certmanager', ['course' => $courseid]);

        foreach ($activities as $activity) {
            $state = state_machine::get_or_create_state($activity->id, $userid);
            if ((int)$state->status === state_machine::STATUS_CERTIFIED) {
                continue;
            }
            if (state_machine::check_completion($activity->id, $userid)) {
                state_machine::award_certification($activity->id, $userid);
            }
        }
    }

    /**
     * React to certification_awarded event.
     *
     * @param \mod_certmanager\event\certification_awarded $event
     */
    public static function certification_awarded(\mod_certmanager\event\certification_awarded $event) {
        // Observers can log or perform additional actions here.
        // Email notifications are already sent by the state_machine.
    }

    /**
     * React to certification_expired event.
     *
     * @param \mod_certmanager\event\certification_expired $event
     */
    public static function certification_expired(\mod_certmanager\event\certification_expired $event) {
        // Observers can log or perform additional actions here.
        // Email notifications are already sent by the state_machine.
    }

    /**
     * React to certification_lapsed event.
     *
     * @param \mod_certmanager\event\certification_lapsed $event
     */
    public static function certification_lapsed(\mod_certmanager\event\certification_lapsed $event) {
        // Observers can log or perform additional actions here.
        // Email notifications are already sent by the state_machine.
    }

    /**
     * React to recert_window_opened event.
     *
     * @param \mod_certmanager\event\recert_window_opened $event
     */
    public static function recert_window_opened(\mod_certmanager\event\recert_window_opened $event) {
        // Observers can log or perform additional actions here.
        // Email notifications are already sent by the state_machine.
    }
}
