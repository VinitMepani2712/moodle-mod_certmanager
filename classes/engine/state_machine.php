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
 * Manages certification state transitions and status tracking
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\engine;

use mod_certmanager\certificate_manager;
use mod_certmanager\expiry_calculator;
use mod_certmanager\notification_manager;
use mod_certmanager\event\certification_awarded;
use mod_certmanager\event\certification_expired;
use mod_certmanager\event\certification_lapsed;
use mod_certmanager\event\recert_window_opened;

/**
 * Certification lifecycle state machine (in progress, certified, expiring, expired, lapsed).
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_machine {
    /** Status: learner is working toward certification. */
    const STATUS_INPROGRESS = 0;
    /** Status: certification currently valid. */
    const STATUS_CERTIFIED = 10;
    /** Status: certification within its recertification window. */
    const STATUS_EXPIRING = 20;
    /** Status: certification validity has lapsed. */
    const STATUS_EXPIRED = 25;
    /** Status: certification lapsed without recertification. */
    const STATUS_LAPSED = 30;

    /** @var state_machine Singleton instance. */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return state_machine
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Fetch the certification state row for a user, creating it if absent.
     *
     * @param mixed $certmanagerid
     * @param mixed $userid
     */
    public static function get_or_create_state($certmanagerid, $userid) {
        global $DB;

        $state = $DB->get_record(
            'certmanager_state',
            ['certmanagerid' => $certmanagerid, 'userid' => $userid]
        );

        if (!$state) {
            $state = new \stdClass();
            $state->certmanagerid = $certmanagerid;
            $state->userid = $userid;
            $state->status = self::STATUS_INPROGRESS;
            $state->progresspct = 0;
            $state->timecertified = 0;
            $state->timeexpires = 0;
            $state->timewindowopens = 0;
            $state->timelapsed = 0;
            $state->usermodified = 0;
            $state->timecreated = time();
            $state->timemodified = time();
            $state->id = $DB->insert_record('certmanager_state', $state);
        }

        return $state;
    }

    /**
     * Manually award a certification to a user.
     *
     * @param mixed $certmanagerid
     * @param mixed $userid
     * @param mixed $expiryoption
     * @param mixed $customdays
     * @param mixed $manualdate
     */
    public static function award_certification(
        $certmanagerid,
        $userid,
        $expiryoption = 'default',
        $customdays = 0,
        $manualdate = 0
    ) {
        global $DB, $USER;

        $instance = $DB->get_record('certmanager', ['id' => $certmanagerid], '*', MUST_EXIST);
        $state = self::get_or_create_state($certmanagerid, $userid);

        if ((int)$state->status === self::STATUS_CERTIFIED) {
            return true;
        }

        $now = time();

        // Calculate expiry using the new expiry_calculator.
        $timeexpires = expiry_calculator::calculate(
            $now,
            $expiryoption,
            (int)$instance->validityperiod,
            (int)$customdays,
            (int)$manualdate
        );

        if ($timeexpires === false) {
            // Validation failed — fall back to default.
            $timeexpires = !empty($instance->validityperiod)
                ? $now + ((int)$instance->validityperiod * 86400)
                : 0;
        }

        $oldstatus = (int)$state->status;
        $state->status = self::STATUS_CERTIFIED;
        $state->progresspct = 100;
        $state->timecertified = $now;
        $state->timeexpires = $timeexpires;

        // Calculate recertification window open date (validity minus window period).
        if (!empty($instance->windowperiod) && $timeexpires > 0) {
            $state->timewindowopens = $timeexpires - ((int)$instance->windowperiod * 86400);
        }

        $state->usermodified = isset($USER->id) ? $USER->id : 0;
        $state->timemodified = $now;

        $DB->update_record('certmanager_state', $state);

        // Log state change.
        self::log_state_change(
            $certmanagerid,
            $userid,
            $oldstatus,
            self::STATUS_CERTIFIED,
            'Awarded (expiry: ' . $expiryoption . ')'
        );

        // Generate certificate PDF with calculated expiry.
        certificate_manager::generate_certificate($certmanagerid, $userid, $now, $timeexpires);

        // Send award notification.
        $cert = new \mod_certmanager\certification($certmanagerid);
        notification_manager::send(
            'awarded',
            $cert,
            $userid,
            get_string('notif_awarded_subject', 'mod_certmanager', $cert->get('name')),
            get_string('notif_awarded_body', 'mod_certmanager', [
                'name' => $cert->get('name'),
                'expiry' => $timeexpires > 0 ? userdate($timeexpires) : get_string('expiryformat_never', 'mod_certmanager'),
            ]),
            $timeexpires
        );

        // Fire certification_awarded event.
        $event = certification_awarded::create([
            'objectid' => $state->id,
            'relateduserid' => $userid,
            'context' => \context_system::instance(),
            'other' => ['certid' => $certmanagerid],
        ]);
        $event->trigger();

        return true;
    }

    /**
     * Record a state transition in the certification history.
     *
     * @param mixed $certmanagerid
     * @param mixed $userid
     * @param mixed $fromstatus
     * @param mixed $tostatus
     * @param mixed $reason
     */
    public static function log_state_change($certmanagerid, $userid, $fromstatus, $tostatus, $reason) {
        global $DB, $USER;

        $record = new \stdClass();
        $record->certmanagerid = $certmanagerid;
        $record->userid = $userid;
        $record->fromstatus = $fromstatus;
        $record->tostatus = $tostatus;
        $record->reason = substr($reason, 0, 100);
        $record->actorid = isset($USER->id) ? $USER->id : 0;
        $record->timecreated = time();

        $DB->insert_record('certmanager_history', $record);
    }

    /**
     * Check whether the user has met the certification requirements.
     *
     * @param mixed $certmanagerid
     * @param mixed $userid
     */
    public static function check_completion($certmanagerid, $userid) {
        global $DB;

        $instance = $DB->get_record('certmanager', ['id' => $certmanagerid], '*', MUST_EXIST);

        if (!$instance->enableautowage) {
            return false;
        }

        $course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);

        // Course-based completion: check if course is marked complete.
        if ($instance->awardtype === 'course') {
            $completion = $DB->get_record(
                'course_completions',
                ['course' => $course->id, 'userid' => $userid]
            );
            return $completion && $completion->timecompleted > 0;
        }

        // Activity-based completion: check selected activities.
        $modinfo = get_fast_modinfo($course);
        $completioninfo = new \completion_info($course);

        if (!$completioninfo->is_enabled()) {
            return false;
        }

        // Get required activities.
        $required = $DB->get_records('certmanager_required', ['certmanagerid' => $certmanagerid], '', 'cmid');
        if (empty($required)) {
            return false;
        }

        $completed = 0;
        $minrequired = (int) $instance->minrequired;
        if ($minrequired === 0) {
            $minrequired = count($required);
        }

        foreach ($required as $rec) {
            if (!isset($modinfo->cms[$rec->cmid])) {
                continue;
            }
            $cm = $modinfo->cms[$rec->cmid];
            if (!$completioninfo->is_enabled($cm)) {
                continue;
            }
            $data = $completioninfo->get_data($cm, false, $userid);
            if (
                $data->completionstate == COMPLETION_COMPLETE ||
                $data->completionstate == COMPLETION_COMPLETE_PASS
            ) {
                $completed++;
            }
        }

        return ($completed >= $minrequired);
    }

    /**
     * Get the human-readable label for a status code.
     *
     * @param mixed $status
     */
    public static function get_status_string($status) {
        $map = [
            self::STATUS_INPROGRESS => 'statusinprogress',
            self::STATUS_CERTIFIED  => 'statuscertified',
            self::STATUS_EXPIRING   => 'statusexpiring',
            self::STATUS_EXPIRED    => 'statusexpired',
            self::STATUS_LAPSED     => 'statuslapsed',
        ];
        if (isset($map[$status])) {
            return $map[$status];
        }
        return 'statusinprogress';
    }

    /**
     * Process time-based state transitions and send notifications.
     *
     * @return array Counts: ['expiring' => int, 'expired' => int, 'lapsed' => int]
     */
    public function process_time_transitions() {
        global $DB;

        $now = time();
        $counts = ['expiring' => 0, 'expired' => 0, 'lapsed' => 0];

        // Transition to EXPIRING: certification past renewal window open date.
        $sql = 'SELECT s.* FROM {certmanager_state} s
                WHERE s.status = :certified
                AND s.timewindowopens > 0
                AND s.timewindowopens <= :now
                AND s.timeexpires > :now';
        $rs = $DB->get_recordset_sql($sql, ['certified' => self::STATUS_CERTIFIED, 'now' => $now]);
        foreach ($rs as $state) {
            $state->status = self::STATUS_EXPIRING;
            $state->timemodified = $now;
            $DB->update_record('certmanager_state', $state);
            self::log_state_change(
                $state->certmanagerid,
                $state->userid,
                self::STATUS_CERTIFIED,
                self::STATUS_EXPIRING,
                'Window opened'
            );

            $cert = new \mod_certmanager\certification($state->certmanagerid);
            notification_manager::send(
                'windowopen',
                $cert,
                $state->userid,
                get_string('notif_window_subject', 'mod_certmanager', $cert->get('name')),
                get_string('notif_window_body', 'mod_certmanager', [
                    'name' => $cert->get('name'),
                    'expiry' => userdate($state->timeexpires),
                ]),
                $state->timeexpires
            );

            // Fire recert_window_opened event.
            $event = recert_window_opened::create([
                'objectid' => $state->id,
                'relateduserid' => $state->userid,
                'context' => \context_system::instance(),
                'other' => ['certid' => $state->certmanagerid],
            ]);
            $event->trigger();

            $counts['expiring']++;
        }
        $rs->close();

        // Transition to EXPIRED: past expiry time.
        $sql = 'SELECT s.* FROM {certmanager_state} s
                WHERE s.status IN (:certified, :expiring)
                AND s.timeexpires > 0
                AND s.timeexpires <= :now';
        [$insql, $inparams] = $DB->get_in_or_equal([self::STATUS_CERTIFIED, self::STATUS_EXPIRING], SQL_PARAMS_NAMED);
        $rs = $DB->get_recordset_select(
            'certmanager_state',
            "status $insql AND timeexpires > 0 AND timeexpires <= :now",
            $inparams + ['now' => $now]
        );
        foreach ($rs as $state) {
            $oldstatus = $state->status;
            $state->status = self::STATUS_EXPIRED;
            $state->timemodified = $now;
            $DB->update_record('certmanager_state', $state);
            self::log_state_change(
                $state->certmanagerid,
                $state->userid,
                $oldstatus,
                self::STATUS_EXPIRED,
                'Certification expired'
            );

            $cert = new \mod_certmanager\certification($state->certmanagerid);
            notification_manager::send(
                'expired',
                $cert,
                $state->userid,
                get_string('notif_expired_subject', 'mod_certmanager', $cert->get('name')),
                get_string('notif_expired_body', 'mod_certmanager', $cert->get('name')),
                $state->timeexpires
            );

            // Fire certification_expired event.
            $event = certification_expired::create([
                'objectid' => $state->id,
                'relateduserid' => $state->userid,
                'context' => \context_system::instance(),
                'other' => ['certid' => $state->certmanagerid],
            ]);
            $event->trigger();

            $counts['expired']++;
        }
        $rs->close();

        // Transition to LAPSED: grace period expired.
        $sql = 'SELECT s.* FROM {certmanager_state} s
                JOIN {certmanager} c ON c.id = s.certmanagerid
                WHERE s.status = :expired
                AND s.timeexpires > 0
                AND (s.timeexpires + c.graceperiod * 86400) <= :now';
        $rs = $DB->get_recordset_sql($sql, ['expired' => self::STATUS_EXPIRED, 'now' => $now]);
        foreach ($rs as $state) {
            $state->status = self::STATUS_LAPSED;
            $state->timemodified = $now;
            $DB->update_record('certmanager_state', $state);
            self::log_state_change(
                $state->certmanagerid,
                $state->userid,
                self::STATUS_EXPIRED,
                self::STATUS_LAPSED,
                'Grace period elapsed'
            );

            $cert = new \mod_certmanager\certification($state->certmanagerid);
            notification_manager::send(
                'lapsed',
                $cert,
                $state->userid,
                get_string('notif_lapsed_subject', 'mod_certmanager', $cert->get('name')),
                get_string('notif_lapsed_body', 'mod_certmanager', $cert->get('name')),
                $state->timeexpires
            );

            // Fire certification_lapsed event.
            $event = certification_lapsed::create([
                'objectid' => $state->id,
                'relateduserid' => $state->userid,
                'context' => \context_system::instance(),
                'other' => ['certid' => $state->certmanagerid],
            ]);
            $event->trigger();

            $counts['lapsed']++;
        }
        $rs->close();

        return $counts;
    }
}
