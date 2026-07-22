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
 * Module settings form for Certification Manager.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form for the certmanager module.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_certmanager_mod_form extends moodleform_mod {
    /**
     * Define the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        // Certification settings section (updated with expiry options).
        $mform->addElement('header', 'settings', 'Certification Settings');

        $mform->addElement('text', 'validityperiod', 'Validity (days)', ['size' => '10']);
        $mform->setType('validityperiod', PARAM_INT);
        $mform->setDefault('validityperiod', 365);
        $mform->addHelpButton('validityperiod', 'validityperiod', 'mod_certmanager');

        $mform->addElement('text', 'windowperiod', 'Recertification Window (days)', ['size' => '10']);
        $mform->setType('windowperiod', PARAM_INT);
        $mform->setDefault('windowperiod', 60);
        $mform->addHelpButton('windowperiod', 'windowperiod', 'mod_certmanager');

        $mform->addElement('text', 'graceperiod', 'Grace Period (days)', ['size' => '10']);
        $mform->setType('graceperiod', PARAM_INT);
        $mform->setDefault('graceperiod', 14);
        $mform->addHelpButton('graceperiod', 'graceperiod', 'mod_certmanager');

        // NEW: Expiry override setting.
        $mform->addElement(
            'advcheckbox',
            'enableexpiryoverride',
            'Allow Manual Expiry Override',
            'Teachers can choose custom expiry dates when manually awarding certificates'
        );
        $mform->setDefault('enableexpiryoverride', 1);
        $mform->addHelpButton('enableexpiryoverride', 'enableexpiryoverride', 'mod_certmanager');

        $mform->addElement('advcheckbox', 'enablecertificate', 'Generate Certificates');
        $mform->setDefault('enablecertificate', 1);

        // Automatic award settings.
        $mform->addElement('header', 'autosettings', 'Automatic Certificate Award');

        $mform->addElement('advcheckbox', 'enableautowage', get_string('enableautowage', 'mod_certmanager'));
        $mform->setDefault('enableautowage', 1);
        $mform->addHelpButton('enableautowage', 'enableautowage', 'mod_certmanager');

        // Award type selector.
        $awardtypes = [
            'course' => get_string('awardtype_course', 'mod_certmanager'),
            'activity' => get_string('awardtype_activity', 'mod_certmanager'),
        ];
        $mform->addElement('select', 'awardtype', get_string('awardtype', 'mod_certmanager'), $awardtypes);
        $mform->setDefault('awardtype', 'activity');
        $mform->addHelpButton('awardtype', 'awardtype', 'mod_certmanager');

        // Get course modules for activity selection.
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $trackable = ['assign', 'quiz', 'scorm', 'h5p', 'lesson'];
        $activities = [];

        foreach ($modinfo->cms as $cm) {
            if (in_array($cm->modname, $trackable)) {
                $activities[$cm->id] = format_string($cm->name) . " ($cm->modname)";
            }
        }

        // Activity selection section (shown when awardtype is 'activity').
        if (!empty($activities)) {
            $mform->addElement('static', 'activitiesinfo', '', get_string('requiredactivities_help', 'mod_certmanager'));

            foreach ($activities as $cmid => $activityname) {
                $mform->addElement('advcheckbox', "activity_$cmid", $activityname);
            }

            $mform->addElement('text', 'minrequired', get_string('minrequired', 'mod_certmanager'), ['size' => '10']);
            $mform->setType('minrequired', PARAM_INT);
            $mform->setDefault('minrequired', 0);
            $mform->addHelpButton('minrequired', 'minrequired', 'mod_certmanager');

            // Hide activity selection when awardtype is 'course'.
            $mform->hideIf('activitiesinfo', 'awardtype', 'eq', 'course');
            foreach ($activities as $cmid => $activityname) {
                $mform->hideIf("activity_$cmid", 'awardtype', 'eq', 'course');
            }
            $mform->hideIf('minrequired', 'awardtype', 'eq', 'course');
        } else {
            $mform->addElement('static', 'noactivities', '', get_string('notrackableactivities', 'mod_certmanager'));
            $mform->hideIf('noactivities', 'awardtype', 'eq', 'course');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validate the submitted form data.
     *
     * @param mixed $data
     * @param mixed $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate validity period.
        if (!empty($data['validityperiod']) && (int)$data['validityperiod'] < 0) {
            $errors['validityperiod'] = 'Validity period must be 0 or greater';
        }

        // Validate window period.
        if (!empty($data['windowperiod']) && (int)$data['windowperiod'] < 0) {
            $errors['windowperiod'] = 'Window period must be 0 or greater';
        }

        // Validate grace period.
        if (!empty($data['graceperiod']) && (int)$data['graceperiod'] < 0) {
            $errors['graceperiod'] = 'Grace period must be 0 or greater';
        }

        // Validate minimum required (only for activity-based awards).
        if ($data['enableautowage'] && $data['awardtype'] === 'activity' && isset($data['minrequired'])) {
            $minreq = (int)$data['minrequired'];
            if ($minreq < 0) {
                $errors['minrequired'] = 'Minimum required must be 0 or greater';
            }
            $selectedcount = 0;
            foreach ($data as $key => $val) {
                if (strpos($key, 'activity_') === 0 && $val) {
                    $selectedcount++;
                }
            }
            if ($selectedcount === 0) {
                $errors['minrequired'] = 'At least one activity must be selected for activity-based awards';
            } else if ($minreq > $selectedcount) {
                $errors['minrequired'] = "Minimum required ({$minreq}) cannot exceed selected activities ({$selectedcount})";
            }
        }

        return $errors;
    }
}
