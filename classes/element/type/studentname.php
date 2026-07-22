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
 * Student name element
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element\type;

use mod_certmanager\element\base;

/**
 * Certificate element: studentname.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentname extends base {
    /**
     * Human-readable name for this element type.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return 'Student name';
    }
    /**
     * Default position and size (mm) for a new element of this type.
     *
     * @return array
     */
    public static function default_geometry(): array {
        return [50.0, 90.0, 197.0, 0.0];
    }
    /**
     * Default font size (points) for a new element of this type.
     *
     * @return int
     */
    public static function default_fontsize(): int {
        return 24;
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
        $this->apply_colour($pdf);
        $pdf->SetFont($this->get_font(), 'B', $this->get_fontsize());
        $w = $this->get_width() > 0 ? $this->get_width() : ($pdf->getPageWidth() - $this->get_posx() - 5);
        $pdf->SetXY($this->get_posx(), $this->get_posy());
        $lineh = max(4, $this->get_fontsize() * 0.4);
        $pdf->MultiCell($w, $lineh, fullname($user), 0, $this->get_alignment(), false, 1, '', '', true, 0, false, true, 0);
    }

    /**
     * Render an HTML preview of this element for the designer.
     *
     * @param \stdClass $certmanager
     * @param \stdClass $course
     * @return string
     */
    public function render_html(\stdClass $certmanager, \stdClass $course): string {
        global $USER;
        return '<strong>' . s(fullname($USER)) . '</strong>';
    }
}
