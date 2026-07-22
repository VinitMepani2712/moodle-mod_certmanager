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
 * Manages certificate template save and load operations
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager;

/**
 * Manages saving, loading and applying certificate design templates.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_manager {
    /**
     * Save the current certificate design as a reusable template.
     *
     * @param mixed $design
     * @param mixed $templatename
     * @param mixed $isshared
     */
    public static function save_as_template($design, $templatename, $isshared = false) {
        global $DB, $USER;

        $template = new \stdClass();
        $template->name = $templatename;
        $template->certificatetitle = $design->certificatetitle ?? '';
        $template->awardtext = $design->awardtext ?? '';
        $template->font = $design->font ?? 'helvetica';
        $template->fontcolor = $design->fontcolor ?? '#000000';
        $template->titlefontsize = $design->titlefontsize ?? 28;
        $template->textfontsize = $design->textfontsize ?? 12;
        $template->signatureline = $design->signatureline ?? '';
        $template->showqrcode = $design->showqrcode ?? 1;
        $template->orientation = $design->orientation ?? 'L';
        $template->userid = $USER->id;
        $template->isshared = $isshared ? 1 : 0;
        $template->timecreated = time();
        $template->timemodified = time();

        return $DB->insert_record('certmanager_templates', $template);
    }

    /**
     * Get the certificate design templates available to a user.
     *
     * @param mixed $userid
     */
    public static function get_user_templates($userid) {
        global $DB;

        return $DB->get_records_select(
            'certmanager_templates',
            'userid = :userid OR isshared = 1',
            ['userid' => $userid],
            'timemodified DESC'
        );
    }

    /**
     * Get a single certificate design template by ID.
     *
     * @param mixed $templateid
     */
    public static function get_template($templateid) {
        global $DB;
        return $DB->get_record('certmanager_templates', ['id' => $templateid]);
    }

    /**
     * Apply a template's design to the given certificate.
     *
     * @param mixed $template
     */
    public static function apply_template($template) {
        $design = new \stdClass();
        $design->certificatetitle = $template->certificatetitle;
        $design->awardtext = $template->awardtext;
        $design->font = $template->font;
        $design->fontcolor = $template->fontcolor;
        $design->titlefontsize = $template->titlefontsize;
        $design->textfontsize = $template->textfontsize;
        $design->signatureline = $template->signatureline;
        $design->showqrcode = $template->showqrcode;
        $design->orientation = $template->orientation;
        return $design;
    }

    /**
     * Delete a certificate design template.
     *
     * @param mixed $templateid
     */
    public static function delete_template($templateid) {
        global $DB;
        return $DB->delete_records('certmanager_templates', ['id' => $templateid]);
    }

    /**
     * Export a certificate design template as portable data.
     *
     * @param mixed $templateid
     */
    public static function export_template($templateid) {
        $template = self::get_template($templateid);
        if (!$template) {
            return null;
        }
        return json_encode((array)$template);
    }

    /**
     * Import a certificate design template from portable data.
     *
     * @param mixed $jsondata
     */
    public static function import_template($jsondata) {
        global $USER;
        $data = json_decode($jsondata, true);
        if (!$data) {
            return false;
        }

        $template = new \stdClass();
        $template->name = $data['name'] ?? 'Imported Template';
        $template->certificatetitle = $data['certificatetitle'] ?? '';
        $template->awardtext = $data['awardtext'] ?? '';
        $template->font = $data['font'] ?? 'helvetica';
        $template->fontcolor = $data['fontcolor'] ?? '#000000';
        $template->titlefontsize = $data['titlefontsize'] ?? 28;
        $template->textfontsize = $data['textfontsize'] ?? 12;
        $template->signatureline = $data['signatureline'] ?? '';
        $template->showqrcode = $data['showqrcode'] ?? 1;
        $template->orientation = $data['orientation'] ?? 'L';
        $template->userid = $USER->id;
        $template->isshared = 0;
        $template->timecreated = time();
        $template->timemodified = time();

        global $DB;
        return $DB->insert_record('certmanager_templates', $template);
    }
}
