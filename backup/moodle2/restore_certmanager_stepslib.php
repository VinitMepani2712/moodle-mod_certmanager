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

/**
 * Structure step to restore one certmanager activity.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_certmanager_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure to be restored.
     *
     * @return array The restore path elements.
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('certmanager', '/activity/certmanager');
        $paths[] = new restore_path_element(
            'certmanager_element',
            '/activity/certmanager/certelements/certelement'
        );
        $paths[] = new restore_path_element(
            'certmanager_required',
            '/activity/certmanager/requireds/required'
        );

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'certmanager_state',
                '/activity/certmanager/states/state'
            );
            $paths[] = new restore_path_element(
                'certmanager_certificate',
                '/activity/certmanager/certificates/certificate'
            );
            $paths[] = new restore_path_element(
                'certmanager_history',
                '/activity/certmanager/histories/history'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the certmanager instance record.
     *
     * @param array $data The parsed element data.
     * @return void
     */
    protected function process_certmanager($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->usermodified = (int) $this->get_mappingid('user', $data->usermodified);

        $newitemid = $DB->insert_record('certmanager', $data);

        // Immediately after inserting the record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process one certificate design element.
     *
     * @param array $data The parsed element data.
     * @return void
     */
    protected function process_certmanager_element($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->certmanagerid = $this->get_new_parentid('certmanager');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('certmanager_elements', $data);

        // The last argument tells the restore that files use this id as itemid.
        $this->set_mapping('certmanager_element', $oldid, $newitemid, true);
    }

    /**
     * Process one required activity rule.
     *
     * The cmid is deliberately left untouched here. Course modules referenced by
     * this rule may not exist yet at this point in the restore, so the value is
     * remapped later in restore_certmanager_activity_task::after_restore().
     *
     * @param array $data The parsed element data.
     * @return void
     */
    protected function process_certmanager_required($data) {
        global $DB;

        $data = (object) $data;

        $data->certmanagerid = $this->get_new_parentid('certmanager');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('certmanager_required', $data);
    }

    /**
     * Process one user certification state record.
     *
     * @param array $data The parsed element data.
     * @return void
     */
    protected function process_certmanager_state($data) {
        global $DB;

        $data = (object) $data;

        $userid = $this->get_mappingid('user', $data->userid);
        if (empty($userid)) {
            // The user was not part of this restore, so skip the record.
            return;
        }

        $data->certmanagerid = $this->get_new_parentid('certmanager');
        $data->userid = $userid;
        $data->usermodified = (int) $this->get_mappingid('user', $data->usermodified);
        $data->timecertified = $this->apply_date_offset($data->timecertified);
        $data->timeexpires = $this->apply_date_offset($data->timeexpires);
        $data->timewindowopens = $this->apply_date_offset($data->timewindowopens);
        $data->timelapsed = $this->apply_date_offset($data->timelapsed);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $DB->insert_record('certmanager_state', $data);
    }

    /**
     * Process one issued certificate record.
     *
     * @param array $data The parsed element data.
     * @return void
     */
    protected function process_certmanager_certificate($data) {
        global $DB;

        $data = (object) $data;

        $userid = $this->get_mappingid('user', $data->userid);
        if (empty($userid)) {
            // The user was not part of this restore, so skip the record.
            return;
        }

        $data->certmanagerid = $this->get_new_parentid('certmanager');
        $data->userid = $userid;
        // File ids are not stable across sites. This is resolved in after_execute().
        $data->fileid = null;
        $data->timecertified = $this->apply_date_offset($data->timecertified);
        $data->timeexpires = $this->apply_date_offset($data->timeexpires);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $DB->insert_record('certmanager_certificates', $data);
    }

    /**
     * Process one state change history record.
     *
     * @param array $data The parsed element data.
     * @return void
     */
    protected function process_certmanager_history($data) {
        global $DB;

        $data = (object) $data;

        $userid = $this->get_mappingid('user', $data->userid);
        if (empty($userid)) {
            // The user was not part of this restore, so skip the record.
            return;
        }

        $data->certmanagerid = $this->get_new_parentid('certmanager');
        $data->userid = $userid;
        $data->actorid = (int) $this->get_mappingid('user', $data->actorid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('certmanager_history', $data);
    }

    /**
     * Restore the files belonging to this activity and repair file references.
     *
     * @return void
     */
    protected function after_execute() {
        // Activity description files.
        $this->add_related_files('mod_certmanager', 'intro', null);

        // Design-level image areas, itemid 0.
        $this->add_related_files('mod_certmanager', 'background', null);
        $this->add_related_files('mod_certmanager', 'logo', null);
        $this->add_related_files('mod_certmanager', 'signature', null);

        // Per element images, itemid mapped through the element mapping.
        $this->add_related_files('mod_certmanager', 'elementfiles', 'certmanager_element');

        // Generated PDFs, itemid mapped through the user mapping.
        $this->add_related_files('mod_certmanager', 'certificates', 'user');

        // Rebuild the fileid pointers now that the files exist on this site.
        $this->fix_certificate_fileids();
    }

    /**
     * Repoint certmanager_certificates.fileid at the freshly restored files.
     *
     * @return void
     */
    protected function fix_certificate_fileids() {
        global $DB;

        $certmanagerid = $this->task->get_activityid();
        $contextid = $this->task->get_contextid();

        $records = $DB->get_records('certmanager_certificates', ['certmanagerid' => $certmanagerid]);
        if (!$records) {
            return;
        }

        $fs = get_file_storage();

        foreach ($records as $record) {
            $files = $fs->get_area_files(
                $contextid,
                'mod_certmanager',
                'certificates',
                $record->userid,
                'timecreated DESC',
                false
            );

            if (!$files) {
                continue;
            }

            $file = reset($files);
            $DB->set_field('certmanager_certificates', 'fileid', $file->get_id(), ['id' => $record->id]);
        }
    }
}