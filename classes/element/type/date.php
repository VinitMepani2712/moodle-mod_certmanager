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
 * Date element
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element\type;

use mod_certmanager\element\base;

/**
 * Renders a date. dateitem: "awarded" or "expires". Optional prefix + format.
 */
class date extends base {
    /**
     * Human-readable name for this element type.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return 'Date';
    }
    /**
     * Default position and size (mm) for a new element of this type.
     *
     * @return array
     */
    public static function default_geometry(): array {
        return [20.0, 175.0, 100.0, 0.0];
    }
    /**
     * Default font size (points) for a new element of this type.
     *
     * @return int
     */
    public static function default_fontsize(): int {
        return 10;
    }

    /**
     * Add this element type's specific fields to the edit form.
     *
     * @param \MoodleQuickForm $mform
     * @param \context $context
     */
    public function add_form_fields(\MoodleQuickForm $mform, \context $context): void {
        global $PAGE;

        $mform->addElement('select', 'dateitem', 'Which date', [
            'awarded' => 'Award date',
            'expires' => 'Expiry date',
        ]);
        $mform->setDefault('dateitem', $this->get_data_field('dateitem', 'awarded'));

        $mform->addElement('text', 'prefix', 'Prefix', ['size' => 30]);
        $mform->setType('prefix', PARAM_TEXT);
        $mform->setDefault('prefix', $this->get_data_field('prefix', 'Awarded: '));

        $mform->addElement('text', 'dateformat', 'Date format (strftime)', ['size' => 20]);
        $mform->setType('dateformat', PARAM_TEXT);
        $mform->setDefault('dateformat', $this->get_data_field('dateformat', ''));

        // Auto-switch date fields using simple JS (no AMD).
        $PAGE->requires->js(new \moodle_url('/mod/certmanager/js/date_element_autofill.js'));
    }

    /**
     * Extract this element's stored data from submitted form values.
     *
     * @param \stdClass $data
     * @param \context $context
     * @return array
     */
    public function extract_data_from_form(\stdClass $data, \context $context): array {
        return [
            'dateitem'   => $data->dateitem ?? 'awarded',
            'prefix'     => $data->prefix ?? '',
            'dateformat' => $data->dateformat ?? '',
        ];
    }

    /**
     * Format a timestamp using the element's configured date format.
     *
     * @param int $ts
     * @return string
     */
    private function format_date(int $ts): string {
        $fmt = (string)$this->get_data_field('dateformat', '');
        if ($fmt === '') {
            $fmt = get_string('strftimedate', 'langconfig');
        }
        return $ts > 0 ? userdate($ts, $fmt) : '';
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
        $item = $this->get_data_field('dateitem', 'awarded');
        $ts = $item === 'expires' ? (int)($cert->timeexpires ?? 0) : (int)($cert->timecertified ?? 0);
        $prefix = (string)$this->get_data_field('prefix', '');
        $text = $prefix . $this->format_date($ts);
        if (trim($text) === '') {
            return;
        }

        $this->apply_colour($pdf);
        $pdf->SetFont($this->get_font(), '', $this->get_fontsize());
        $w = $this->get_width() > 0 ? $this->get_width() : ($pdf->getPageWidth() - $this->get_posx() - 5);
        $pdf->SetXY($this->get_posx(), $this->get_posy());
        $lineh = max(4, $this->get_fontsize() * 0.4);
        $pdf->MultiCell($w, $lineh, $text, 0, $this->get_alignment(), false, 1, '', '', true, 0, false, true, 0);
    }

    /**
     * Render an HTML preview of this element for the designer.
     *
     * @param \stdClass $certmanager
     * @param \stdClass $course
     * @return string
     */
    public function render_html(\stdClass $certmanager, \stdClass $course): string {
        $prefix = (string)$this->get_data_field('prefix', '');

        if ($prefix === '') {
            $item = $this->get_data_field('dateitem', 'awarded');
            return $item === 'expires' ? s('Expires: YYYY-MM-DD') : s('Awarded: YYYY-MM-DD');
        }

        // Show ONLY prefix + placeholder.
        return s($prefix . 'YYYY-MM-DD');
    }
}
