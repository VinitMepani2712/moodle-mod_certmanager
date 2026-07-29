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
use context_module;

/**
 * Privacy API provider for mod_certmanager.
 *
 * Handles export and deletion of user data stored in the certmanager plugin.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        // Table: certmanager_state
        // Stores the user's certification state (status, dates, progress).
        $collection->add_database_table('certmanager_state', [
            'userid' => 'privacy:metadata:userid',
            'usermodified' => 'privacy:metadata:usermodified',
            'status' => 'privacy:metadata:status',
            'progresspct' => 'privacy:metadata:progresspct',
            'timecertified' => 'privacy:metadata:timecertified',
            'timeexpires' => 'privacy:metadata:timeexpires',
            'timewindowopens' => 'privacy:metadata:timewindowopens',
            'timelapsed' => 'privacy:metadata:timelapsed',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:certmanager_state');

        // Table: certmanager_certificates
        // Stores generated certificate metadata and generated PDF files.
        $collection->add_database_table('certmanager_certificates', [
            'userid' => 'privacy:metadata:userid',
            'code' => 'privacy:metadata:code',
            'codehash' => 'privacy:metadata:codehash',
            'timecertified' => 'privacy:metadata:timecertified',
            'timeexpires' => 'privacy:metadata:timeexpires',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:certmanager_certificates');

        // Table: certmanager_history
        // Stores audit trail of state changes (who changed status, when, why).
        $collection->add_database_table('certmanager_history', [
            'userid' => 'privacy:metadata:userid',
            'actorid' => 'privacy:metadata:actorid',
            'fromstatus' => 'privacy:metadata:fromstatus',
            'tostatus' => 'privacy:metadata:tostatus',
            'reason' => 'privacy:metadata:reason',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:certmanager_history');

        // File area: certificates
        // Stores generated certificate PDF files. Itemid is the userid.
        $collection->add_files('mod_certmanager', 'certificates', 'privacy:metadata:certificates');

        return $collection;
    }

    /**
     * Get the contexts for a user where data is stored.
     *
     * For certmanager, all user data is stored at the module level.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        $sql = "
            SELECT DISTINCT c.id
              FROM {context} c
              INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = ?
              INNER JOIN {certmanager} cert ON cert.id = cm.instance
             WHERE (
                   EXISTS (SELECT 1 FROM {certmanager_state} WHERE userid = ? AND certmanagerid = cert.id)
                OR EXISTS (SELECT 1 FROM {certmanager_certificates} WHERE userid = ? AND certmanagerid = cert.id)
                OR EXISTS (SELECT 1 FROM {certmanager_history} WHERE userid = ? AND certmanagerid = cert.id)
             )
        ";

        $contextlist->add_from_sql($sql, [
            CONTEXT_MODULE,
            $userid,
            $userid,
            $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get users in a specific context.
     *
     * @param userlist $userlist The userlist to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_module) {
            return;
        }

        $sql = "
            SELECT DISTINCT userid
              FROM {certmanager_state}
             WHERE certmanagerid = (
                   SELECT instance FROM {course_modules} WHERE id = ?
             )
            UNION
            SELECT DISTINCT userid
              FROM {certmanager_certificates}
             WHERE certmanagerid = (
                   SELECT instance FROM {course_modules} WHERE id = ?
             )
            UNION
            SELECT DISTINCT userid
              FROM {certmanager_history}
             WHERE certmanagerid = (
                   SELECT instance FROM {course_modules} WHERE id = ?
             )
        ";

        $userlist->add_from_sql('userid', $sql, [
            $context->instanceid,
            $context->instanceid,
            $context->instanceid,
        ]);
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

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $certid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$certid) {
                continue;
            }

            $subcontext = [get_string('pluginname', 'mod_certmanager')];

            // Export certification state.
            $state = $DB->get_record('certmanager_state', [
                'certmanagerid' => $certid,
                'userid' => $userid,
            ]);

            if ($state) {
                $stateexport = (object) [
                    'status' => (int) $state->status,
                    'progress_percent' => (int) $state->progresspct,
                    'time_certified' => $state->timecertified ? transform::datetime($state->timecertified) : null,
                    'time_expires' => $state->timeexpires ? transform::datetime($state->timeexpires) : null,
                    'time_window_opens' => $state->timewindowopens ? transform::datetime($state->timewindowopens) : null,
                    'time_lapsed' => $state->timelapsed ? transform::datetime($state->timelapsed) : null,
                    'time_created' => transform::datetime($state->timecreated),
                    'time_modified' => transform::datetime($state->timemodified),
                ];

                if ($state->usermodified > 0) {
                    $stateexport->last_modified_by = transform::user($state->usermodified);
                }

                writer::with_context($context)->export_data(
                    array_merge($subcontext, [get_string('privacy:path:state', 'mod_certmanager')]),
                    $stateexport
                );
            }

            // Export generated certificates.
            $certificate = $DB->get_record('certmanager_certificates', [
                'certmanagerid' => $certid,
                'userid' => $userid,
            ]);

            if ($certificate) {
                $certexport = (object) [
                    'verification_code' => $certificate->code,
                    'time_certified' => $certificate->timecertified ? transform::datetime($certificate->timecertified) : null,
                    'time_expires' => $certificate->timeexpires ? transform::datetime($certificate->timeexpires) : null,
                    'time_created' => transform::datetime($certificate->timecreated),
                    'time_modified' => transform::datetime($certificate->timemodified),
                ];

                writer::with_context($context)->export_data(
                    array_merge($subcontext, [get_string('privacy:path:certificates', 'mod_certmanager')]),
                    $certexport
                );

                // Export the certificate PDF file.
                writer::with_context($context)->export_files(
                    array_merge($subcontext, [get_string('privacy:path:certificates', 'mod_certmanager')]),
                    'certificates'
                );
            }

            // Export state change history.
            $history = $DB->get_records('certmanager_history', [
                'certmanagerid' => $certid,
                'userid' => $userid,
            ], 'timecreated ASC');

            if ($history) {
                $historyexport = [];
                foreach ($history as $entry) {
                    $historyexport[] = (object) [
                        'from_status' => (int) $entry->fromstatus,
                        'to_status' => (int) $entry->tostatus,
                        'reason' => $entry->reason,
                        'actor' => $entry->actorid > 0 ? transform::user($entry->actorid) : null,
                        'time_created' => transform::datetime($entry->timecreated),
                    ];
                }

                writer::with_context($context)->export_data(
                    array_merge($subcontext, [get_string('privacy:path:history', 'mod_certmanager')]),
                    (object) ['entries' => $historyexport]
                );
            }
        }
    }

    /**
     * Delete all user data in a context (used by site admins to purge all data).
     *
     * @param context $context The context to purge.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if (!$context instanceof context_module) {
            return;
        }

        $certid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
        if (!$certid) {
            return;
        }

        // Delete state records for this certification.
        $states = $DB->get_records('certmanager_state', ['certmanagerid' => $certid]);
        foreach ($states as $state) {
            $DB->delete_records('certmanager_history', [
                'certmanagerid' => $certid,
                'userid' => $state->userid,
            ]);
        }
        $DB->delete_records('certmanager_state', ['certmanagerid' => $certid]);

        // Delete certificate records and files.
        $fs = get_file_storage();
        $certificates = $DB->get_records('certmanager_certificates', ['certmanagerid' => $certid]);
        foreach ($certificates as $cert) {
            $fs->delete_area_files($context->id, 'mod_certmanager', 'certificates', $cert->userid);
        }
        $DB->delete_records('certmanager_certificates', ['certmanagerid' => $certid]);
    }

    /**
     * Delete all data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts for the user.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $fs = get_file_storage();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $certid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$certid) {
                continue;
            }

            // Delete state record and associated history.
            $DB->delete_records('certmanager_history', [
                'certmanagerid' => $certid,
                'userid' => $userid,
            ]);
            $DB->delete_records('certmanager_state', [
                'certmanagerid' => $certid,
                'userid' => $userid,
            ]);

            // Delete certificate and its files.
            $fs->delete_area_files($context->id, 'mod_certmanager', 'certificates', $userid);
            $DB->delete_records('certmanager_certificates', [
                'certmanagerid' => $certid,
                'userid' => $userid,
            ]);
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

        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $certid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
        if (!$certid) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete state records and associated history for all these users.
        $DB->delete_records_select('certmanager_history', "certmanagerid = ? AND userid $insql",
            array_merge([$certid], $inparams));
        $DB->delete_records_select('certmanager_state', "certmanagerid = ? AND userid $insql",
            array_merge([$certid], $inparams));

        // Delete certificates and their files for all these users.
        $fs = get_file_storage();
        $certificates = $DB->get_records_select('certmanager_certificates',
            "certmanagerid = ? AND userid $insql", array_merge([$certid], $inparams));

        foreach ($certificates as $cert) {
            $fs->delete_area_files($context->id, 'mod_certmanager', 'certificates', $cert->userid);
        }

        $DB->delete_records_select('certmanager_certificates', "certmanagerid = ? AND userid $insql",
            array_merge([$certid], $inparams));
    }
}