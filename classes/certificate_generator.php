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
 * Builds a certificate PDF from stored elements
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager;

use mod_certmanager\element\factory;

/**
 * Builds a certificate PDF from stored elements.
 *
 * Renders the "background" element first (full-page), then all other elements in
 * sort order. Each element is fully self-contained — this class just walks the
 * list and asks each one to render itself.
 */
class certificate_generator {
    /**
     * Generate the PDF for a specific user and return it as a binary string.
     *
     * @param \stdClass $certmanager Activity instance.
     * @param \stdClass $user User record.
     * @param \stdClass $cert Certificate issue record.
     * @return string PDF binary.
     */
    public function generate(\stdClass $certmanager, \stdClass $user, \stdClass $cert): string {
        global $CFG, $DB;

        require_once($CFG->libdir . '/tcpdf/tcpdf.php');

        $course = $DB->get_record('course', ['id' => $certmanager->course], '*', MUST_EXIST);

        $orientation = ($certmanager->orientation ?? 'L') === 'P' ? 'P' : 'L';
        $format = [(float)$certmanager->pagewidth, (float)$certmanager->pageheight];
        // TCPDF wants the "unrotated" size — for landscape we still pass 297x210 with 'L'.
        $pdf = new \TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('Moodle Certification Manager');
        $pdf->SetAuthor('Moodle');
        $pdf->SetTitle(format_string($certmanager->name));
        $pdf->AddPage();

        $elements = factory::get_elements((int)$certmanager->id);

        // Background first (special-cased: always at z=0).
        foreach ($elements as $el) {
            if ($el->get_type() === 'background') {
                $el->render_pdf($pdf, $user, $certmanager, $course, $cert);
            }
        }

        // Everything else in sort order.
        foreach ($elements as $el) {
            if ($el->get_type() === 'background') {
                continue;
            }
            try {
                $el->render_pdf($pdf, $user, $certmanager, $course, $cert);
            } catch (\Throwable $e) {
                debugging('certmanager element render failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $pdf->Output('', 'S');
    }
}
