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
 * Text element with placeholders
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element\type;

use mod_certmanager\element\base;

/**
 * Text element: static text with optional {placeholders}.
 * Supported placeholders: {fullname}, {firstname}, {lastname}, {coursename},
 * {certificatename}, {date}, {expiredate}, {code}, {site}.
 */
class text extends base {
    /**
     * Human-readable name for this element type.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return 'Text';
    }
    /**
     * Default position and size (mm) for a new element of this type.
     *
     * @return array
     */
    public static function default_geometry(): array {
        return [50.0, 50.0, 197.0, 0.0];
    }
    /**
     * Default font size (points) for a new element of this type.
     *
     * @return int
     */
    public static function default_fontsize(): int {
        return 14;
    }

    /**
     * Add this element type's specific fields to the edit form.
     *
     * @param \MoodleQuickForm $mform
     * @param \context $context
     */
    public function add_form_fields(\MoodleQuickForm $mform, \context $context): void {
        $mform->addElement(
            'textarea',
            'text',
            get_string('text_content', 'mod_certmanager'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('text', PARAM_RAW);
        $mform->setDefault('text', $this->get_data_field('text', ''));
        $mform->addElement(
            'static',
            'placeholders_hint',
            '',
            '<small>You can use: {fullname}, {firstname}, {lastname}, {coursename}, ' .
            '{certificatename}, {date}, {expiredate}, {code}, {site}</small>'
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
        return ['text' => $data->text ?? ''];
    }

    /**
     * Substitute placeholders with real values.
     * @param string $tpl The template string
     * @param \stdClass $user The user object
     * @param \stdClass $certmanager The certification manager object
     * @param \stdClass $course The course object
     * @param \stdClass $cert The certificate object
     * @return string The substituted template
     */
    public static function substitute(
        string $tpl,
        \stdClass $user,
        \stdClass $certmanager,
        \stdClass $course,
        \stdClass $cert
    ): string {
        global $CFG;
        $awarded = !empty($cert->timecertified) ? userdate($cert->timecertified, get_string('strftimedate', 'langconfig')) : '';
        $expires = !empty($cert->timeexpires) ? userdate($cert->timeexpires, get_string('strftimedate', 'langconfig')) : '';
        $map = [
            '{fullname}'        => fullname($user),
            '{firstname}'       => $user->firstname ?? '',
            '{lastname}'        => $user->lastname ?? '',
            '{coursename}'      => format_string($course->fullname ?? ''),
            '{certificatename}' => format_string($certmanager->name ?? ''),
            '{date}'            => $awarded,
            '{expiredate}'      => $expires,
            '{code}'            => $cert->code ?? '',
            '{site}'            => format_string($CFG->wwwroot ?? ''),
        ];
        return strtr($tpl, $map);
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
        $text = self::substitute(
            (string)$this->get_data_field('text', ''),
            $user,
            $certmanager,
            $course,
            $cert
        );
        if ($text === '') {
            return;
        }

        $this->apply_colour($pdf);
        $pdf->SetFont($this->get_font(), '', $this->get_fontsize());
        $w = $this->get_width() > 0 ? $this->get_width() : ($pdf->getPageWidth() - $this->get_posx() - 5);
        $pdf->SetXY($this->get_posx(), $this->get_posy());
        // MultiCell height calc: use font size in mm (points → mm ≈ pt * 0.353).
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
        $t = (string)$this->get_data_field('text', '');
        if ($t === '') {
            $t = 'Text';
        }
        return s($t);
    }
}
