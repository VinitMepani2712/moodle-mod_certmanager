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

namespace mod_certmanager\engine;

use mod_certmanager\certification;

/**
 * Creates and removes user allocations to certifications.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_manager {
    /**
     * Assign a user to a certification (idempotent) and run an initial evaluation.
     *
     * @param certification $cert Certification.
     * @param int $userid User id.
     * @param int $source 0 for manual, otherwise the rule id responsible.
     * @param int $actorid Acting user id (0 = system).
     * @return bool True if a new assignment was created.
     */
    public static function assign(certification $cert, int $userid, int $source = 0, int $actorid = 0): bool {
        global $DB;

        $certid = (int) $cert->get('id');
        if ($DB->record_exists('mod_certmanager_assign', ['certid' => $certid, 'userid' => $userid])) {
            return false;
        }

        $now = time();
        $DB->insert_record('mod_certmanager_assign', (object) [
            'certid' => $certid,
            'userid' => $userid,
            'source' => $source,
            'assignerid' => $actorid,
            'usermodified' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $sm = state_machine::instance();
        $state = $sm->get_or_create_state($certid, $userid);
        $DB->insert_record('mod_certmanager_history', (object) [
            'certid' => $certid,
            'userid' => $userid,
            'fromstatus' => -1,
            'tostatus' => (int) $state->status,
            'reason' => $source ? 'ruleassigned' : 'manualassigned',
            'actorid' => $actorid,
            'timecreated' => $now,
        ]);

        // Certify immediately if the path is already satisfied.
        $sm->reevaluate_user($cert, $userid);
        return true;
    }

    /**
     * Remove a user's allocation (state and history are retained for audit).
     *
     * @param int $certid Certification id.
     * @param int $userid User id.
     * @param int $actorid Acting user id.
     * @return void
     */
    public static function unassign(int $certid, int $userid, int $actorid = 0): void {
        global $DB;
        $DB->delete_records('mod_certmanager_assign', ['certid' => $certid, 'userid' => $userid]);
        $DB->insert_record('mod_certmanager_history', (object) [
            'certid' => $certid,
            'userid' => $userid,
            'fromstatus' => -1,
            'tostatus' => -1,
            'reason' => 'unassigned',
            'actorid' => $actorid,
            'timecreated' => time(),
        ]);
    }
}
