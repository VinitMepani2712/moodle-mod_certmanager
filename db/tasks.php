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
 * Scheduled task definitions for Certification Manager plugin.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$tasks = [
    [
        'classname' => 'mod_certmanager\task\evaluate_states',
        'blocking' => 0,
        'minute' => '15', 'hour' => '*', 'day' => '*', 'month' => '*', 'dayofweek' => '*',
    ],
    [
        'classname' => 'mod_certmanager\task\process_notifications',
        'blocking' => 0,
        'minute' => '30', 'hour' => '*', 'day' => '*', 'month' => '*', 'dayofweek' => '*',
    ],
    [
        'classname' => 'mod_certmanager\task\sync_rules',
        'blocking' => 0,
        'minute' => '45', 'hour' => '2', 'day' => '*', 'month' => '*', 'dayofweek' => '*',
    ],
];
