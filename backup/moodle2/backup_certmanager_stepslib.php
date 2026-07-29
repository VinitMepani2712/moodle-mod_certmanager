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

/**
 * Defines the complete certmanager structure for backup, with file and id annotations.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_certmanager_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure of the certmanager activity backup.
     *
     * @return backup_nested_element The wrapped activity structure.
     */
    protected function define_structure() {

        // Are we including user info in this backup?
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $certmanager = new backup_nested_element('certmanager', ['id'], [
            'name',
            'intro',
            'introformat',
            'validityperiod',
            'windowperiod',
            'graceperiod',
            'enablecertificate',
            'enableautowage',
            'awardtype',
            'minrequired',
            'orientation',
            'pagewidth',
            'pageheight',
            'timemodified',
            'usermodified',
            'timecreated',
        ]);

        $certelements = new backup_nested_element('certelements');
        $certelement = new backup_nested_element('certelement', ['id'], [
            'element',
            'name',
            'data',
            'font',
            'fontsize',
            'colour',
            'posx',
            'posy',
            'width',
            'height',
            'alignment',
            'sortorder',
            'timemodified',
        ]);

        $requireds = new backup_nested_element('requireds');
        $required = new backup_nested_element('required', ['id'], [
            'cmid',
            'timecreated',
        ]);

        $states = new backup_nested_element('states');
        $state = new backup_nested_element('state', ['id'], [
            'userid',
            'status',
            'progresspct',
            'timecertified',
            'timeexpires',
            'timewindowopens',
            'timelapsed',
            'usermodified',
            'timecreated',
            'timemodified',
        ]);

        $certificates = new backup_nested_element('certificates');
        $certificate = new backup_nested_element('certificate', ['id'], [
            'userid',
            'code',
            'codehash',
            'timecertified',
            'timeexpires',
            'timecreated',
            'timemodified',
        ]);

        $histories = new backup_nested_element('histories');
        $history = new backup_nested_element('history', ['id'], [
            'userid',
            'fromstatus',
            'tostatus',
            'reason',
            'actorid',
            'timecreated',
        ]);

        // Build the tree.
        $certmanager->add_child($certelements);
        $certelements->add_child($certelement);

        $certmanager->add_child($requireds);
        $requireds->add_child($required);

        $certmanager->add_child($states);
        $states->add_child($state);

        $certmanager->add_child($certificates);
        $certificates->add_child($certificate);

        $certmanager->add_child($histories);
        $histories->add_child($history);

        // Define sources.
        $certmanager->set_source_table('certmanager', ['id' => backup::VAR_ACTIVITYID]);

        $certelement->set_source_table(
            'certmanager_elements',
            ['certmanagerid' => backup::VAR_PARENTID],
            'sortorder ASC, id ASC'
        );

        $required->set_source_table(
            'certmanager_required',
            ['certmanagerid' => backup::VAR_PARENTID],
            'id ASC'
        );

        // User data sources: only included when userinfo is requested.
        if ($userinfo) {
            $state->set_source_table(
                'certmanager_state',
                ['certmanagerid' => backup::VAR_PARENTID],
                'id ASC'
            );

            $certificate->set_source_table(
                'certmanager_certificates',
                ['certmanagerid' => backup::VAR_PARENTID],
                'id ASC'
            );

            $history->set_source_table(
                'certmanager_history',
                ['certmanagerid' => backup::VAR_PARENTID],
                'id ASC'
            );
        }

        // Define id annotations.
        $certmanager->annotate_ids('user', 'usermodified');

        // Required activities point at other course modules in the same course.
        // Annotating them is what makes the remap in after_restore() possible.
        $required->annotate_ids('course_module', 'cmid');

        $state->annotate_ids('user', 'userid');
        $state->annotate_ids('user', 'usermodified');
        $certificate->annotate_ids('user', 'userid');
        $history->annotate_ids('user', 'userid');
        $history->annotate_ids('user', 'actorid');

        // Define file annotations.
        // Activity description files (itemid is not used).
        $certmanager->annotate_files('mod_certmanager', 'intro', null);

        // Design-level image areas always use itemid 0.
        $certmanager->annotate_files('mod_certmanager', 'background', null);
        $certmanager->annotate_files('mod_certmanager', 'logo', null);
        $certmanager->annotate_files('mod_certmanager', 'signature', null);

        // Per-element images use the element id as itemid.
        $certelement->annotate_files('mod_certmanager', 'elementfiles', 'id');

        // Generated certificate PDFs use the user id as itemid.
        $certificate->annotate_files('mod_certmanager', 'certificates', 'userid');

        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($certmanager);
    }
}