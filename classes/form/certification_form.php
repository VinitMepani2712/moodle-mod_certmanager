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

namespace mod_certmanager\form;

use mod_certmanager\certification;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Create/edit form for a certification.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification_form extends moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'text',
            'name',
            get_string('certificationname', 'mod_certmanager'),
            ['size' => 60, 'maxlength' => 255]
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'idnumber', get_string('idnumber'), ['size' => 30, 'maxlength' => 100]);
        $mform->setType('idnumber', PARAM_TEXT);

        $mform->addElement(
            'textarea',
            'description',
            get_string('description'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('description', PARAM_RAW);

        $mform->addElement(
            'course',
            'courses',
            get_string('pathcourses', 'mod_certmanager'),
            ['multiple' => true]
        );
        $mform->addRule('courses', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('courses', 'pathcourses', 'mod_certmanager');

        $logicoptions = [
            certification::LOGIC_ALL => get_string('logicall', 'mod_certmanager'),
            certification::LOGIC_MIN => get_string('logicmin', 'mod_certmanager'),
        ];
        $mform->addElement('select', 'logictype', get_string('logictype', 'mod_certmanager'), $logicoptions);
        $mform->setType('logictype', PARAM_INT);

        $mform->addElement('text', 'minrequired', get_string('minrequired', 'mod_certmanager'), ['size' => 4]);
        $mform->setType('minrequired', PARAM_INT);
        $mform->setDefault('minrequired', 1);
        $mform->hideIf('minrequired', 'logictype', 'eq', certification::LOGIC_ALL);

        $mform->addElement(
            'duration',
            'validityperiod',
            get_string('validityperiod', 'mod_certmanager'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->addHelpButton('validityperiod', 'validityperiod', 'mod_certmanager');

        $mform->addElement(
            'duration',
            'windowperiod',
            get_string('windowperiod', 'mod_certmanager'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->addHelpButton('windowperiod', 'windowperiod', 'mod_certmanager');

        $mform->addElement(
            'duration',
            'graceperiod',
            get_string('graceperiod', 'mod_certmanager'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->addHelpButton('graceperiod', 'graceperiod', 'mod_certmanager');

        $mform->addElement('advcheckbox', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);

        $this->add_action_buttons();
    }

    /**
     * Server-side validation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors keyed by element name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['courses'])) {
            $errors['courses'] = get_string('required');
        }
        if ((int) $data['logictype'] === certification::LOGIC_MIN) {
            $count = is_array($data['courses'] ?? null) ? count($data['courses']) : 0;
            if ((int) $data['minrequired'] < 1 || (int) $data['minrequired'] > $count) {
                $errors['minrequired'] = get_string('errorminrequired', 'mod_certmanager');
            }
        }
        if (
            !empty($data['windowperiod']) && !empty($data['validityperiod'])
                && (int) $data['windowperiod'] >= (int) $data['validityperiod']
        ) {
            $errors['windowperiod'] = get_string('errorwindowperiod', 'mod_certmanager');
        }
        if (!empty($data['windowperiod']) && empty($data['validityperiod'])) {
            $errors['windowperiod'] = get_string('errorwindownovalidity', 'mod_certmanager');
        }
        return $errors;
    }
}
