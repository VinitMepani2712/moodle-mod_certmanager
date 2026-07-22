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
 * Signature line element
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element\type;

use mod_certmanager\element\base;

/**
 * Certificate element: signatureline.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signatureline extends base {
    /**
     * Human-readable name for this element type.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return 'Signature line';
    }
    /**
     * Default position and size (mm) for a new element of this type.
     *
     * @return array
     */
    public static function default_geometry(): array {
        return [50.0, 165.0, 80.0, 0.0];
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
        $mform->addElement('text', 'label', 'Label', ['size' => 40]);
        $mform->setType('label', PARAM_TEXT);
        $mform->setDefault('label', $this->get_data_field('label', 'Director of Training'));
    }

    /**
     * Extract this element's stored data from submitted form values.
     *
     * @param \stdClass $data
     * @param \context $context
     * @return array
     */
    public function extract_data_from_form(\stdClass $data, \context $context): array {
        return ['label' => $data->label ?? ''];
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
        $label = (string)$this->get_data_field('label', '');
        if ($label === '') {
            return;
        }

        $this->apply_colour($pdf);
        $pdf->SetFont($this->get_font(), '', $this->get_fontsize());
        $w = $this->get_width() > 0 ? $this->get_width() : 80.0;
        $x = $this->get_posx();
        $y = $this->get_posy();

        // Rule above.
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Line($x, $y, $x + $w, $y);
        $pdf->SetXY($x, $y + 1);
        $lineh = max(4, $this->get_fontsize() * 0.4);
        $pdf->MultiCell($w, $lineh, $label, 0, $this->get_alignment() ?: 'C', false, 1, '', '', true, 0, false, true, 0);
    }

    /**
     * Render an HTML preview of this element for the designer.
     *
     * @param \stdClass $certmanager
     * @param \stdClass $course
     * @return string
     */
    public function render_html(\stdClass $certmanager, \stdClass $course): string {
        $label = (string)$this->get_data_field('label', 'Signature');
        return '<div style="border-top:1px solid #333;padding-top:2px;text-align:center;">' . s($label) . '</div>';
    }
}
