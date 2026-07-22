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

namespace mod_certmanager\task;

use mod_certmanager\engine\rule_evaluator;

/**
 * Scheduled task: nightly reconciliation of dynamic assignment rules.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_rules extends \core\task\scheduled_task {
    /**
     * Localised task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksyncrules', 'mod_certmanager');
    }

    /**
     * Run the task.
     *
     * @return void
     */
    public function execute() {
        if (!get_config('mod_certmanager', 'enablerules')) {
            mtrace('mod_certmanager: dynamic rules disabled, skipping.');
            return;
        }
        $created = rule_evaluator::instance()->sync_all();
        mtrace("mod_certmanager: rule sync created $created new assignments.");
    }
}
