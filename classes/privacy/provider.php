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

namespace mod_certmanager\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use context;
use context_system;

/**
 * Privacy API provider for mod_certmanager.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** @var string[] Tables holding per-user data. */
    const USERTABLES = [
        'mod_certmanager_assign',
        'mod_certmanager_state',
        'mod_certmanager_history',
        'mod_certmanager_notif_log',
    ];

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('mod_certmanager_assign', [
            'userid' => 'privacy:metadata:userid',
            'certid' => 'privacy:metadata:certid',
            'source' => 'privacy:metadata:source',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:assign');

        $collection->add_database_table('mod_certmanager_state', [
            'userid' => 'privacy:metadata:userid',
            'certid' => 'privacy:metadata:certid',
            'status' => 'privacy:metadata:status',
            'timecertified' => 'privacy:metadata:timecertified',
            'timeexpires' => 'privacy:metadata:timeexpires',
        ], 'privacy:metadata:state');

        $collection->add_database_table('mod_certmanager_history', [
            'userid' => 'privacy:metadata:userid',
            'certid' => 'privacy:metadata:certid',
            'fromstatus' => 'privacy:metadata:status',
            'tostatus' => 'privacy:metadata:status',
            'reason' => 'privacy:metadata:reason',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:history');

        $collection->add_database_table('mod_certmanager_notif_log', [
            'userid' => 'privacy:metadata:userid',
            'certid' => 'privacy:metadata:certid',
            'notiftype' => 'privacy:metadata:notiftype',
            'timesent' => 'privacy:metadata:timesent',
        ], 'privacy:metadata:notiflog');

        return $collection;
    }

    /**
     * Contexts containing data for a user (all data lives in the system context).
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        foreach (self::USERTABLES as $table) {
            if ($DB->record_exists($table, ['userid' => $userid])) {
                $contextlist->add_system_context();
                break;
            }
        }
        return $contextlist;
    }

    /**
     * Users with data in a given context.
     *
     * @param userlist $userlist The userlist to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        if (!$userlist->get_context() instanceof context_system) {
            return;
        }
        foreach (self::USERTABLES as $table) {
            $userlist->add_from_sql('userid', "SELECT userid FROM {{$table}}", []);
        }
    }

    /**
     * Export all certification data for a user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $hassystem = false;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_system) {
                $hassystem = true;
            }
        }
        if (!$hassystem) {
            return;
        }

        $context = context_system::instance();
        $subcontext = [get_string('pluginname', 'mod_certmanager')];

        $sql = 'SELECT s.*, c.name AS certname
                  FROM {mod_certmanager_state} s
                  JOIN {mod_certmanager_cert} c ON c.id = s.certid
                 WHERE s.userid = :userid';
        $states = $DB->get_records_sql($sql, ['userid' => $userid]);

        $export = [];
        foreach ($states as $state) {
            $export[] = (object) [
                'certification' => format_string($state->certname),
                'status' => (int) $state->status,
                'timecertified' => $state->timecertified
                    ? transform::datetime($state->timecertified) : null,
                'timeexpires' => $state->timeexpires
                    ? transform::datetime($state->timeexpires) : null,
            ];
        }
        writer::with_context($context)->export_data($subcontext, (object) ['certifications' => $export]);

        $history = $DB->get_records('mod_certmanager_history', ['userid' => $userid], 'timecreated ASC');
        $historyexport = [];
        foreach ($history as $entry) {
            $historyexport[] = (object) [
                'certid' => (int) $entry->certid,
                'fromstatus' => (int) $entry->fromstatus,
                'tostatus' => (int) $entry->tostatus,
                'reason' => $entry->reason,
                'timecreated' => transform::datetime($entry->timecreated),
            ];
        }
        writer::with_context($context)->export_data(
            array_merge($subcontext, [get_string('privacy:path:history', 'mod_certmanager')]),
            (object) ['history' => $historyexport]
        );
    }

    /**
     * Delete all user data in a context.
     *
     * @param context $context The context to purge.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;
        if (!$context instanceof context_system) {
            return;
        }
        foreach (self::USERTABLES as $table) {
            $DB->delete_records($table);
        }
    }

    /**
     * Delete all data for one user.
     *
     * @param approved_contextlist $contextlist Approved contexts for the user.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_system) {
                foreach (self::USERTABLES as $table) {
                    $DB->delete_records($table, ['userid' => $userid]);
                }
            }
        }
    }

    /**
     * Delete data for multiple users in a context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if (!$userlist->get_context() instanceof context_system) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        foreach (self::USERTABLES as $table) {
            $DB->delete_records_select($table, "userid $insql", $inparams);
        }
    }
}
