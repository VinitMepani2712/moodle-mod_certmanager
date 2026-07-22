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

namespace mod_certmanager\task;

use mod_certmanager\certification;
use mod_certmanager\engine\state_machine;
use mod_certmanager\notification_manager;

/**
 * Scheduled task: send expiry reminder notifications.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_notifications extends \core\task\scheduled_task {
    /**
     * Localised task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskprocessnotifications', 'mod_certmanager');
    }

    /**
     * Run the task.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $daysconfig = (string) get_config('mod_certmanager', 'reminderdays');
        $offsets = array_filter(
            array_map('intval', explode(',', $daysconfig)),
            static fn($d) => $d > 0
        );
        if (empty($offsets)) {
            return;
        }
        rsort($offsets);
        $now = time();
        $sent = 0;

        [$insql, $inparams] = $DB->get_in_or_equal(
            [state_machine::STATUS_CERTIFIED, state_machine::STATUS_EXPIRING],
            SQL_PARAMS_NAMED
        );

        foreach ($offsets as $days) {
            $rs = $DB->get_recordset_select(
                'mod_certmanager_state',
                "status $insql AND timeexpires > :now AND timeexpires <= :threshold",
                $inparams + ['now' => $now, 'threshold' => $now + ($days * DAYSECS)]
            );
            foreach ($rs as $state) {
                $cert = new certification($state->certid);
                $ok = notification_manager::send(
                    'reminder',
                    $cert,
                    (int) $state->userid,
                    get_string('notif_reminder_subject', 'mod_certmanager', [
                        'name' => $cert->get('name'), 'days' => $days,
                    ]),
                    get_string('notif_reminder_body', 'mod_certmanager', [
                        'name' => $cert->get('name'),
                        'expiry' => userdate($state->timeexpires),
                    ]),
                    (int) $state->timeexpires,
                    'reminder' . $days
                );
                $sent += $ok ? 1 : 0;
            }
            $rs->close();
        }
        mtrace("mod_certmanager: processed reminders ($sent sends attempted).");
    }
}
