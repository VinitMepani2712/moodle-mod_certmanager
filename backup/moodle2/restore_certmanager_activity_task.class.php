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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/certmanager/backup/moodle2/restore_certmanager_stepslib.php');

/**
 * Provides the steps to perform one complete restore of a certmanager activity.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_certmanager_activity_task extends restore_activity_task {

    /**
     * Define particular settings this activity can have.
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define particular steps this activity can have.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_certmanager_activity_structure_step('certmanager_structure', 'certmanager.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('certmanager', ['intro'], 'certmanager');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity to be executed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('CERTMANAGERVIEWBYID', '/mod/certmanager/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CERTMANAGERINDEX', '/mod/certmanager/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied by the
     * restore_logs_processor when restoring certmanager logs.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('certmanager', 'add', 'view.php?id={course_module}', '{certmanager}');
        $rules[] = new restore_log_rule('certmanager', 'update', 'view.php?id={course_module}', '{certmanager}');
        $rules[] = new restore_log_rule('certmanager', 'view', 'view.php?id={course_module}', '{certmanager}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied by the
     * restore_logs_processor when restoring course logs.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('certmanager', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

    /**
     * Remap course module references stored by this activity.
     *
     * The certmanager_required table stores raw course module ids pointing at other
     * activities in the same course. Those modules may be restored after this one,
     * so the remapping has to happen once every activity task has finished.
     *
     * @return void
     */
    public function after_restore() {
        global $DB;

        $certmanagerid = $this->get_activityid();

        $records = $DB->get_records('certmanager_required', ['certmanagerid' => $certmanagerid]);
        foreach ($records as $record) {
            $mapping = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $record->cmid);

            if ($mapping && !empty($mapping->newitemid)) {
                // Guard against the unique (certmanagerid, cmid) index if the target already exists.
                $duplicate = $DB->record_exists_select(
                    'certmanager_required',
                    'certmanagerid = :certmanagerid AND cmid = :cmid AND id <> :id',
                    ['certmanagerid' => $certmanagerid, 'cmid' => $mapping->newitemid, 'id' => $record->id]
                );

                if ($duplicate) {
                    $DB->delete_records('certmanager_required', ['id' => $record->id]);
                } else {
                    $DB->set_field('certmanager_required', 'cmid', $mapping->newitemid, ['id' => $record->id]);
                }
            } else {
                // The referenced activity was not part of this restore, so the rule is meaningless.
                $DB->delete_records('certmanager_required', ['id' => $record->id]);
            }
        }
    }
}