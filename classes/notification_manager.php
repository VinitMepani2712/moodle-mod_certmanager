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

namespace mod_certmanager;

use core_user;
use moodle_url;

/**
 * Sends certification lifecycle notifications with idempotency logging.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_manager {
    /**
     * Send one message via a plugin message provider, at most once per cycle.
     *
     * @param string $provider Message provider name (see db/messages.php).
     * @param certification $cert Certification concerned.
     * @param int $userid Recipient user id.
     * @param string $subject Message subject.
     * @param string $body Plain-text body.
     * @param int $cyclekey Expiry timestamp identifying the certification cycle (0 if n/a).
     * @param string $logtype Idempotency key type; defaults to the provider name. Use a
     *                        distinct value (e.g. 'reminder30') when the same provider may
     *                        legitimately fire more than once per cycle.
     * @return bool True if sent (or already sent), false on failure.
     */
    public static function send(
        string $provider,
        certification $cert,
        int $userid,
        string $subject,
        string $body,
        int $cyclekey = 0,
        string $logtype = ''
    ): bool {
        global $DB;

        $logkey = [
            'certid' => $cert->get('id'),
            'userid' => $userid,
            'notiftype' => $logtype !== '' ? $logtype : $provider,
            'cyclekey' => $cyclekey,
        ];
        if ($DB->record_exists('mod_certmanager_notif_log', $logkey)) {
            return true;
        }

        $user = core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user || $user->deleted || $user->suspended) {
            return false;
        }

        $message = new \core\message\message();
        $message->component = 'mod_certmanager';
        $message->name = $provider;
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = text_to_html($body, false, false, true);
        $message->smallmessage = $subject;
        $message->notification = 1;
        $message->courseid = SITEID;
        $message->contexturl = (new moodle_url('/local/certmanager/my.php'))->out(false);
        $message->contexturlname = get_string('mycertifications', 'mod_certmanager');

        if (message_send($message)) {
            $log = (object) $logkey;
            $log->timesent = time();
            $DB->insert_record('mod_certmanager_notif_log', $log);
            return true;
        }
        return false;
    }
}
