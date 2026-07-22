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

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to manually assign users to a certification.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_form extends moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'certid', $this->_customdata['certid']);
        $mform->setType('certid', PARAM_INT);

        $options = [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => true,
            'valuehtmlcallback' => function ($userid): string {
                $user = \core_user::get_user($userid);
                return $user ? fullname($user) : '';
            },
        ];
        $mform->addElement(
            'autocomplete',
            'userids',
            get_string('selectusers', 'mod_certmanager'),
            [],
            $options
        );
        $mform->addRule('userids', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('assignusers', 'mod_certmanager'));
    }
}
