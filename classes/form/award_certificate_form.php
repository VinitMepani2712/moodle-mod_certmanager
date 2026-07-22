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
 * Form for manually awarding a certification
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

use mod_certmanager\expiry_calculator;

/**
 * Form for manually awarding a certification with expiry date selection.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_certificate_form extends \moodleform {
    /**
     * Define the form fields.
     */
    public function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $instance = $this->_customdata['instance'];
        $userid = $this->_customdata['userid'];

        // User info (read-only).
        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $mform->addElement(
            'static',
            'userinfo',
            'User',
            '<strong>' . fullname($user) . '</strong> (' . $user->email . ')'
        );

        // Expiry option selector.
        $mform->addElement('header', 'expiry_header', 'Certification Expiry');

        $expiryoptions = expiry_calculator::get_expiry_options();
        $selectoptions = [];
        foreach ($expiryoptions as $key => [$label, $desc]) {
            $selectoptions[$key] = $label . ' — ' . $desc;
        }

        $mform->addElement('select', 'expiryoption', 'Expiry Option', $selectoptions);
        $mform->setType('expiryoption', PARAM_ALPHANUMEXT);
        $mform->setDefault('expiryoption', 'default');
        $mform->addHelpButton('expiryoption', 'expiryoption', 'mod_certmanager');

        // Custom days (shown only when 'custom' is selected).
        $mform->addElement('text', 'customdays', 'Number of Days', ['size' => 10]);
        $mform->setType('customdays', PARAM_INT);
        $mform->hideIf('customdays', 'expiryoption', 'neq', 'custom');
        $mform->addHelpButton('customdays', 'customdays', 'mod_certmanager');

        // Manual date picker (shown only when 'manual' is selected).
        $mform->addElement(
            'date_time_selector',
            'manualdate',
            'Expiry Date & Time',
            ['optional' => false, 'startyear' => date('Y'), 'stopyear' => date('Y') + 5]
        );
        $mform->hideIf('manualdate', 'expiryoption', 'neq', 'manual');
        $mform->addHelpButton('manualdate', 'manualdate', 'mod_certmanager');

        // Preview box.
        $mform->addElement('static', 'preview_note', '', '');

        // Hidden fields.
        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'award');
        $mform->setType('action', PARAM_ALPHA);

        // Buttons.
        $this->add_action_buttons(true, 'Award Certificate');
    }

    /**
     * Validate the submitted form data.
     *
     * @param mixed $data
     * @param mixed $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate expiry options.
        $validationerrors = expiry_calculator::validate_form_data(
            $data['expiryoption'],
            (int)$data['customdays'],
            (int)$data['manualdate']
        );
        $errors = array_merge($errors, $validationerrors);

        return $errors;
    }

    /**
     * Calculate the expiry timestamp for a certification.
     */
    public function get_calculated_expiry_timestamp() {
        $data = $this->get_data();
        if (!$data) {
            return false;
        }

        $instance = $this->_customdata['instance'];
        return expiry_calculator::calculate(
            time(),
            $data->expiryoption,
            (int)$instance->validityperiod,
            (int)($data->customdays ?? 0),
            (int)($data->manualdate ?? 0)
        );
    }
}
