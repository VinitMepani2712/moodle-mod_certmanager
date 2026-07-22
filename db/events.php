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
 * Event observers for Certification Manager plugin.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback'  => '\mod_certmanager\observer::course_module_completed',
    ],
    [
        'eventname' => '\mod_certmanager\event\certification_awarded',
        'callback'  => '\mod_certmanager\observer::certification_awarded',
    ],
    [
        'eventname' => '\mod_certmanager\event\certification_expired',
        'callback'  => '\mod_certmanager\observer::certification_expired',
    ],
    [
        'eventname' => '\mod_certmanager\event\certification_lapsed',
        'callback'  => '\mod_certmanager\observer::certification_lapsed',
    ],
    [
        'eventname' => '\mod_certmanager\event\recert_window_opened',
        'callback'  => '\mod_certmanager\observer::recert_window_opened',
    ],
];
