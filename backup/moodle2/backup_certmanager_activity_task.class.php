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

require_once($CFG->dirroot . '/mod/certmanager/backup/moodle2/backup_certmanager_stepslib.php');

/**
 * Provides the steps to perform one complete backup of a certmanager activity.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_certmanager_activity_task extends backup_activity_task {
    /**
     * Define particular settings this activity can have.
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define particular steps this activity can have.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new backup_certmanager_activity_structure_step('certmanager_structure', 'certmanager.xml'));
    }

    /**
     * Encode all the content links of this activity to be transportable.
     *
     * @param string $content Content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/certmanager', '#');

        // Link to the list of certmanager instances in a course.
        $content = preg_replace(
            "#($base)/index\.php\?id=(\d+)#",
            '$@CERTMANAGERINDEX*$2@$',
            $content
        );

        // Link to a certmanager view page by course module id.
        $content = preg_replace(
            "#($base)/view\.php\?id=(\d+)#",
            '$@CERTMANAGERVIEWBYID*$2@$',
            $content
        );

        return $content;
    }
}