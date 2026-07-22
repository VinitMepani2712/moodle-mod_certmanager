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
 * Generic image element
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element\type;

use mod_certmanager\element\base;
use mod_certmanager\element\image_helper;

/**
 * Generic image element (logo, signature, decorative graphic).
 * Uses the "elementfiles" file area, itemid = element id.
 */
class image extends base {
    /**
     * Human-readable name for this element type.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return 'Image';
    }
    /**
     * Default position and size (mm) for a new element of this type.
     *
     * @return array
     */
    public static function default_geometry(): array {
        return [20.0, 20.0, 40.0, 0.0];
    }
    /**
     * Default font size (points) for a new element of this type.
     *
     * @return int
     */
    public static function default_fontsize(): int {
        return 12;
    }

    /**
     * Add this element type's specific fields to the edit form.
     *
     * @param \MoodleQuickForm $mform
     * @param \context $context
     */
    public function add_form_fields(\MoodleQuickForm $mform, \context $context): void {
        $mform->addElement(
            'filemanager',
            'imagefile',
            'Image',
            null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
        );
    }

    /**
     * Extract this element's stored data from submitted form values.
     *
     * @param \stdClass $data
     * @param \context $context
     * @return array
     */
    public function extract_data_from_form(\stdClass $data, \context $context): array {
        return [];
    }

    /**
     * Handle side-effects (such as file moves) after the element is saved.
     *
     * @param \stdClass $data
     * @param \context $context
     */
    public function after_save(\stdClass $data, \context $context): void {
        if (isset($data->imagefile)) {
            file_save_draft_area_files(
                $data->imagefile,
                $context->id,
                'mod_certmanager',
                'elementfiles',
                $this->get_id(),
                ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
            );
        }
    }

    /**
     * Clean up any stored files before the element is deleted.
     *
     * @param \context $context
     */
    public function before_delete(\context $context): void {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_certmanager', 'elementfiles', $this->get_id());
    }

    /**
     * Render this element onto the certificate PDF.
     *
     * @param \TCPDF $pdf
     * @param \stdClass $user
     * @param \stdClass $certmanager
     * @param \stdClass $course
     * @param \stdClass $cert
     */
    public function render_pdf(
        \TCPDF $pdf,
        \stdClass $user,
        \stdClass $certmanager,
        \stdClass $course,
        \stdClass $cert
    ): void {
        $cm = get_coursemodule_from_instance('certmanager', $certmanager->id, $course->id, false, MUST_EXIST);
        $ctx = \context_module::instance($cm->id);
        $img = image_helper::get_temp_image($ctx->id, 'elementfiles', $this->get_id());
        if (!$img) {
            return;
        }

        try {
            $w = $this->get_width() > 0 ? $this->get_width() : 0;
            $h = $this->get_height() > 0 ? $this->get_height() : 0;
            $pdf->Image(
                $img['path'],
                $this->get_posx(),
                $this->get_posy(),
                $w,
                $h,
                $img['type'],
                '',
                '',
                false,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                false
            );
        } catch (\Exception $e) {
            debugging('mod_certmanager image render failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        image_helper::cleanup($img);
    }

    /**
     * Render an HTML preview of this element for the designer.
     *
     * @param \stdClass $certmanager
     * @param \stdClass $course
     * @return string
     */
    public function render_html(\stdClass $certmanager, \stdClass $course): string {
        $cm = get_coursemodule_from_instance('certmanager', $certmanager->id, $course->id, false, MUST_EXIST);
        $ctx = \context_module::instance($cm->id);
        $url = image_helper::get_url($ctx->id, 'elementfiles', $this->get_id());
        if (!$url) {
            return '<div class="certmanager-placeholder">[Image not uploaded]</div>';
        }
        return '<img src="' . $url . '" style="width:100%;height:100%;object-fit:contain;" alt="">';
    }
}
