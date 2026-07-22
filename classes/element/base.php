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
 * Base class for all certificate elements
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element;

/**
 * Base class for all certificate elements.
 *
 * Each element type (text, image, studentname, coursename, etc.) extends this class
 * and implements at minimum render_pdf() and render_html(). Elements have a position,
 * size, styling and a type-specific `data` blob (JSON).
 *
 * @package    mod_certmanager
 * @copyright  2026 Certification Manager
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var \stdClass Element record from certmanager_elements. */
    protected $record;

    /** @var array Decoded element-specific data. */
    protected $data;

    /**
     * Constructor.
     *
     * @param \stdClass $record Row from certmanager_elements.
     */
    public function __construct(\stdClass $record) {
        $this->record = $record;
        $decoded = !empty($record->data) ? json_decode($record->data, true) : [];
        $this->data = is_array($decoded) ? $decoded : [];
    }

    /**
     * Element ID.
     */
    public function get_id(): int {
        return (int)$this->record->id;
    }

    /**
     * Element type key (e.g. "text", "image").
     */
    public function get_type(): string {
        return (string)$this->record->element;
    }

    /**
     * Display name shown on the editor.
     */
    public function get_name(): string {
        return (string)($this->record->name ?: static::get_display_name());
    }

    /**
     * Get the element X position in millimetres.
     *
     * @return float
     */
    public function get_posx(): float {
        return (float)$this->record->posx;
    }
    /**
     * Get the element Y position in millimetres.
     *
     * @return float
     */
    public function get_posy(): float {
        return (float)$this->record->posy;
    }
    /**
     * Get the element width in millimetres.
     *
     * @return float
     */
    public function get_width(): float {
        return (float)$this->record->width;
    }
    /**
     * Get the element height in millimetres.
     *
     * @return float
     */
    public function get_height(): float {
        return (float)$this->record->height;
    }
    /**
     * Get the element font family.
     *
     * @return string
     */
    public function get_font(): string {
        return (string)($this->record->font ?: 'helvetica');
    }
    /**
     * Get the element font size in points.
     *
     * @return int
     */
    public function get_fontsize(): int {
        return (int)($this->record->fontsize ?: 12);
    }
    /**
     * Get the element colour as a hex string.
     *
     * @return string
     */
    public function get_colour(): string {
        return (string)($this->record->colour ?: '#000000');
    }
    /**
     * Get the element text alignment.
     *
     * @return string
     */
    public function get_alignment(): string {
        return (string)($this->record->alignment ?: 'L');
    }
    /**
     * Get a single value from the element's decoded data blob.
     *
     * @param mixed $key
     * @param mixed $default
     */
    public function get_data_field($key, $default = null) {
        return $this->data[$key] ?? $default;
    }
    /**
     * Get the raw database record.
     *
     * @return \stdClass
     */
    public function get_record(): \stdClass {
        return $this->record;
    }
    /**
     * Get the decoded element data array.
     *
     * @return array
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     * Set the pdf text colour from #rrggbb.
     * @param \TCPDF $pdf The PDF object
     */
    protected function apply_colour(\TCPDF $pdf): void {
        $hex = ltrim($this->get_colour(), '#');
        if (strlen($hex) !== 6) {
            $hex = '000000';
        }
        $pdf->SetTextColor(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    /**
     * Render the element into the PDF.
     *
     * @param \TCPDF $pdf The PDF object.
     * @param \stdClass $user Recipient user record.
     * @param \stdClass $certmanager Activity instance record.
     * @param \stdClass $course Course record.
     * @param \stdClass $cert Certificate issue record (has code, timecertified, timeexpires).
     */
    abstract public function render_pdf(
        \TCPDF $pdf,
        \stdClass $user,
        \stdClass $certmanager,
        \stdClass $course,
        \stdClass $cert
    ): void;

    /**
     * Render an HTML preview of the element (used in the designer).
     *
     * @param \stdClass $certmanager Activity instance record.
     * @param \stdClass $course Course record.
     * @return string HTML fragment.
     */
    abstract public function render_html(\stdClass $certmanager, \stdClass $course): string;

    /**
     * Human-friendly name for this element type (used in the "Add element" menu).
     */
    public static function get_display_name(): string {
        return 'Element';
    }

    /**
     * Definition of type-specific form fields.
     * Called on the edit-element form.
     *
     * @param \MoodleQuickForm $mform
     * @param \context $context
     */
    public function add_form_fields(\MoodleQuickForm $mform, \context $context): void {
        // Default: no extra fields. Subclasses override.
    }

    /**
     * Convert submitted form data → the JSON blob stored in `data`.
     * Return an associative array; base class handles JSON-encoding.
     *
     * @param \stdClass $data Form submission.
     * @param \context $context
     * @return array
     */
    public function extract_data_from_form(\stdClass $data, \context $context): array {
        return [];
    }

    /**
     * Called after the element record is created/updated, so the element can
     * handle side-effects (e.g. moving files out of the draft area).
     *
     * @param \stdClass $data Form submission.
     * @param \context $context
     */
    public function after_save(\stdClass $data, \context $context): void {
        // Default: no-op. Subclasses that use file uploads override.
    }

    /**
     * Called when the element is being deleted, so the subclass can clean up files.
     *
     * @param \context $context
     */
    public function before_delete(\context $context): void {
        // Default: no-op.
    }

    /**
     * Default sensible position for a freshly-created element (mm).
     *
     * @return array [posx, posy, width, height]
     */
    public static function default_geometry(): array {
        return [50.0, 50.0, 100.0, 0.0];
    }

    /**
     * Default font size for a freshly-created element.
     */
    public static function default_fontsize(): int {
        return 12;
    }
}
