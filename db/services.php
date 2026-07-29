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
 * External API services for mod_certmanager.
 *
 * Registers external API methods that can be called via AJAX using Moodle's core/ajax service.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$services = [
    // Service for certificate designer AJAX operations.
    'mod_certmanager_designer' => [
        'functions' => [
            'mod_certmanager_save_element_geometry',
            'mod_certmanager_reorder_elements',
        ],
        'restrictedusers' => 0,  // Service is available to all authenticated users.
        'enabled' => 1,
    ],
];

$functions = [
    // Save element position/size from designer canvas.
    'mod_certmanager_save_element_geometry' => [
        'classname' => 'mod_certmanager\external\save_element_geometry',
        'methodname' => 'save_element_geometry',
        'classpath' => 'mod/certmanager/classes/external/save_element_geometry.php',
        'description' => 'Save certificate element geometry (position and size)',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:manageactivities',
    ],
    // Reorder elements via side panel drag-drop.
    'mod_certmanager_reorder_elements' => [
        'classname' => 'mod_certmanager\external\reorder_elements',
        'methodname' => 'reorder_elements',
        'classpath' => 'mod/certmanager/classes/external/reorder_elements.php',
        'description' => 'Reorder certificate elements',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:manageactivities',
    ],
];