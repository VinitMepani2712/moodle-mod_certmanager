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

use mod_certmanager\engine\rule_evaluator;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to add a dynamic assignment rule to a certification.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_form extends moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'certid', $this->_customdata['certid']);
        $mform->setType('certid', PARAM_INT);

        $types = [
            rule_evaluator::TYPE_COHORT => get_string('rulecohort', 'mod_certmanager'),
            rule_evaluator::TYPE_PROFILEFIELD => get_string('ruleprofilefield', 'mod_certmanager'),
        ];
        $mform->addElement('select', 'ruletype', get_string('ruletype', 'mod_certmanager'), $types);
        $mform->setType('ruletype', PARAM_INT);

        $cohorts = $DB->get_records_menu('cohort', ['visible' => 1], 'name ASC', 'id, name');
        $cohorts = array_map('format_string', $cohorts);
        $mform->addElement('select', 'cohortid', get_string('cohort', 'cohort'), $cohorts);
        $mform->setType('cohortid', PARAM_INT);
        $mform->hideIf('cohortid', 'ruletype', 'neq', rule_evaluator::TYPE_COHORT);

        $fields = array_combine(rule_evaluator::CORE_FIELDS, array_map(
            static fn($f) => get_string($f),
            rule_evaluator::CORE_FIELDS
        ));
        $customfields = $DB->get_records_menu('user_info_field', [], 'name ASC', 'shortname, name');
        foreach ($customfields as $shortname => $name) {
            $fields[$shortname] = format_string($name);
        }
        $mform->addElement('select', 'field', get_string('profilefield', 'mod_certmanager'), $fields);
        $mform->setType('field', PARAM_ALPHANUMEXT);
        $mform->hideIf('field', 'ruletype', 'neq', rule_evaluator::TYPE_PROFILEFIELD);

        $mform->addElement('text', 'value', get_string('fieldvalue', 'mod_certmanager'), ['size' => 40]);
        $mform->setType('value', PARAM_TEXT);
        $mform->hideIf('value', 'ruletype', 'neq', rule_evaluator::TYPE_PROFILEFIELD);

        $this->add_action_buttons(true, get_string('addrule', 'mod_certmanager'));
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
        if (
            (int) $data['ruletype'] === rule_evaluator::TYPE_PROFILEFIELD
                && trim((string) ($data['value'] ?? '')) === ''
        ) {
            $errors['value'] = get_string('required');
        }
        if ((int) $data['ruletype'] === rule_evaluator::TYPE_COHORT && empty($data['cohortid'])) {
            $errors['cohortid'] = get_string('required');
        }
        return $errors;
    }
}
