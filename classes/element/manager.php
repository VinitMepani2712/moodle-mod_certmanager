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
 * CRUD helper for certificate elements
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element;

/**
 * CRUD helper for certificate elements.
 */
class manager {
    /**
     * Create a new element with sensible defaults.
     * @param int $certmanagerid The certificate manager ID
     * @param string $type The element type
     * @param string $name The element name
     * @return int The new element ID
     */
    public static function create(int $certmanagerid, string $type, string $name = ''): int {
        global $DB;

        $types = factory::get_types();
        if (!isset($types[$type])) {
            throw new \moodle_exception('invalidelementtype', 'mod_certmanager', '', $type);
        }
        $class = $types[$type];

        [$posx, $posy, $width, $height] = call_user_func([$class, 'default_geometry']);
        $fontsize = call_user_func([$class, 'default_fontsize']);
        $displayname = $name !== '' ? $name : call_user_func([$class, 'get_display_name']);

        $record = new \stdClass();
        $record->certmanagerid = $certmanagerid;
        $record->element = $type;
        $record->name = $displayname;
        $record->data = null;
        $record->font = 'helvetica';
        $record->fontsize = $fontsize;
        $record->colour = '#000000';
        $record->posx = $posx;
        $record->posy = $posy;
        $record->width = $width;
        $record->height = $height;
        $record->alignment = 'L';
        // Background elements always live at sortorder 0 so they render first.
        $record->sortorder = ($type === 'background') ? 0 : self::next_sortorder($certmanagerid);
        $record->timemodified = time();

        return $DB->insert_record('certmanager_elements', $record);
    }

    /**
     * Get a value.
     *
     * @param int $id
     * @return ?base
     */
    public static function get(int $id): ?base {
        global $DB;
        $row = $DB->get_record('certmanager_elements', ['id' => $id]);
        if (!$row) {
            return null;
        }
        return factory::instance($row);
    }

    /**
     * Update the position and size of an element.
     *
     * @param int $id
     * @param array $fields
     */
    public static function update_geometry(int $id, array $fields): void {
        global $DB;
        $allowed = ['posx', 'posy', 'width', 'height', 'font', 'fontsize',
            'colour', 'alignment', 'name', 'sortorder'];
        $update = ['id' => $id, 'timemodified' => time()];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $fields)) {
                $update[$k] = $fields[$k];
            }
        }
        $DB->update_record('certmanager_elements', (object)$update);
    }

    /**
     * Set default values on the form.
     *
     * @param int $id
     * @param array $data
     */
    public static function set_data(int $id, array $data): void {
        global $DB;
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id,
            'data' => json_encode($data),
            'timemodified' => time(),
        ]);
    }

    /**
     * Delete the record.
     *
     * @param int $id
     * @param \context $context
     */
    public static function delete(int $id, \context $context): void {
        global $DB;
        $element = self::get($id);
        if ($element) {
            $element->before_delete($context);
        }
        $DB->delete_records('certmanager_elements', ['id' => $id]);
    }

    /**
     * Duplicate an element (including uploaded files) with a small position
     * offset so the copy is visible on the canvas.
     * @param int $id The element ID to duplicate
     * @param \context $context The context for file operations
     * @return ?int The new element ID or null
     */
    public static function duplicate(int $id, \context $context): ?int {
        global $DB;
        $src = $DB->get_record('certmanager_elements', ['id' => $id]);
        if (!$src) {
            return null;
        }

        // Background can only exist once (per spec: full-page image).
        if ($src->element === 'background') {
            return null;
        }

        $new = clone $src;
        unset($new->id);
        $new->sortorder = self::next_sortorder((int)$src->certmanagerid);
        $new->posx = min((float)$src->posx + 5, 250);
        $new->posy = min((float)$src->posy + 5, 200);
        $new->name = ($src->name ?? '') . ' (copy)';
        $new->timemodified = time();
        $newid = $DB->insert_record('certmanager_elements', $new);

        // Copy any uploaded files (images, signatures, etc).
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_certmanager', 'elementfiles', $id, '', false);
        foreach ($files as $f) {
            $newrec = [
                'contextid' => $context->id,
                'component' => 'mod_certmanager',
                'filearea' => 'elementfiles',
                'itemid' => $newid,
                'filepath' => $f->get_filepath(),
                'filename' => $f->get_filename(),
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $fs->create_file_from_storedfile($newrec, $f);
        }
        return $newid;
    }

    /**
     * Move an element up (direction = -1) or down (direction = +1) in the
     * sort order by swapping with its immediate neighbour.
     * Background elements aren't reorderable — they stay at sortorder 0.
     * @param int $id The element ID to move
     * @param int $direction The direction: -1 up, +1 down
     */
    public static function move(int $id, int $direction): void {
        global $DB;
        $el = $DB->get_record('certmanager_elements', ['id' => $id]);
        if (!$el || $el->element === 'background') {
            return;
        }

        // Find nearest neighbour (non-background) in that direction.
        if ($direction < 0) {
            $sql = "SELECT * FROM {certmanager_elements}
                    WHERE certmanagerid = ? AND element <> 'background' AND sortorder < ?
                    ORDER BY sortorder DESC";
        } else {
            $sql = "SELECT * FROM {certmanager_elements}
                    WHERE certmanagerid = ? AND element <> 'background' AND sortorder > ?
                    ORDER BY sortorder ASC";
        }
        $siblings = $DB->get_records_sql($sql, [$el->certmanagerid, $el->sortorder], 0, 1);
        if (!$siblings) {
            return;
        }
        $sib = reset($siblings);

        // Two-step swap through a temporary value to avoid unique-key clash if any.
        $now = time();
        $tmp = -1 * (int)$el->id;
        $DB->update_record('certmanager_elements', (object)['id' => $el->id, 'sortorder' => $tmp, 'timemodified' => $now]);
        $DB->update_record(
            'certmanager_elements',
            (object)['id' => $sib->id, 'sortorder' => (int)$el->sortorder, 'timemodified' => $now]
        );
        $DB->update_record(
            'certmanager_elements',
            (object)['id' => $el->id, 'sortorder' => (int)$sib->sortorder, 'timemodified' => $now]
        );
    }

    /**
     * Get the next available sort order value.
     *
     * @param int $certmanagerid
     * @return int
     */
    private static function next_sortorder(int $certmanagerid): int {
        global $DB;
        $max = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {certmanager_elements} WHERE certmanagerid = ?',
            [$certmanagerid]
        );
        return (int)$max + 1;
    }

    /**
     * Export all elements for an activity to a portable JSON string.
     * Does NOT include uploaded files or IDs — those are activity-specific.
     * @param \stdClass $certmanager The certificate manager object
     * @return string The JSON-encoded elements
     */
    public static function export_json(\stdClass $certmanager): string {
        global $DB;
        $rows = $DB->get_records(
            'certmanager_elements',
            ['certmanagerid' => $certmanager->id],
            'sortorder ASC, id ASC'
        );
        $elements = [];
        foreach ($rows as $r) {
            $elements[] = [
                'element' => $r->element,
                'name' => $r->name,
                'data' => $r->data,
                'font' => $r->font,
                'fontsize' => (int)$r->fontsize,
                'colour' => $r->colour,
                'posx' => (float)$r->posx,
                'posy' => (float)$r->posy,
                'width' => (float)$r->width,
                'height' => (float)$r->height,
                'alignment' => $r->alignment,
                'sortorder' => (int)$r->sortorder,
            ];
        }
        return json_encode([
            'version'     => '2.0',
            'plugin'      => 'mod_certmanager',
            'exported'    => time(),
            'source_name' => $certmanager->name,
            'orientation' => $certmanager->orientation ?? 'L',
            'pagewidth'   => (float)($certmanager->pagewidth ?? 297),
            'pageheight'  => (float)($certmanager->pageheight ?? 210),
            'elements'    => $elements,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import elements from a JSON template. Returns count imported.
     *
     * @param int $certmanagerid
     * @param string $json
     * @param bool $replace If true, delete existing elements first.
     * @param \context $context
     * @return int Number of elements imported.
     */
    public static function import_json(int $certmanagerid, string $json, bool $replace, \context $context): int {
        global $DB;
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['elements']) || !is_array($data['elements'])) {
            throw new \moodle_exception('Invalid template file (not a mod_certmanager export)');
        }

        if ($replace) {
            $existing = $DB->get_records('certmanager_elements', ['certmanagerid' => $certmanagerid]);
            foreach ($existing as $row) {
                self::delete((int)$row->id, $context);
            }
        }

        $count = 0;
        $baseorder = self::next_sortorder($certmanagerid);
        $types = factory::get_types();
        foreach ($data['elements'] as $i => $el) {
            if (!isset($el['element']) || !isset($types[$el['element']])) {
                continue;
            }
            $rec = new \stdClass();
            $rec->certmanagerid = $certmanagerid;
            $rec->element   = $el['element'];
            $rec->name      = (string)($el['name'] ?? '');
            $rec->data      = $el['data'] ?? null;
            $rec->font      = (string)($el['font'] ?? 'helvetica');
            $rec->fontsize  = (int)($el['fontsize'] ?? 12);
            $rec->colour    = (string)($el['colour'] ?? '#000000');
            $rec->posx      = (float)($el['posx'] ?? 0);
            $rec->posy      = (float)($el['posy'] ?? 0);
            $rec->width     = (float)($el['width'] ?? 0);
            $rec->height    = (float)($el['height'] ?? 0);
            $rec->alignment = (string)($el['alignment'] ?? 'L');
            $rec->sortorder = $el['element'] === 'background' ? 0 : ($baseorder + $i);
            $rec->timemodified = time();
            $DB->insert_record('certmanager_elements', $rec);
            $count++;
        }
        return $count;
    }

    /**
     * Seed a default 8-element starter layout.
     * @param int $certmanagerid The certificate manager ID
     * @param bool $force Force recreation even if elements exist
     */
    public static function seed_defaults(int $certmanagerid, bool $force = false): void {
        global $DB;
        if (!$force) {
            $count = $DB->count_records('certmanager_elements', ['certmanagerid' => $certmanagerid]);
            if ($count > 0) {
                return;
            }
        }

        $id = self::create($certmanagerid, 'text', 'Title');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 25, 'width' => 257,
            'fontsize' => 32, 'alignment' => 'C',
            'data' => json_encode(['text' => 'Certificate of Completion']),
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'text', 'Intro line');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 70, 'width' => 257,
            'fontsize' => 14, 'alignment' => 'C',
            'data' => json_encode(['text' => 'This certifies that']),
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'studentname', 'Student name');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 85, 'width' => 257,
            'fontsize' => 24, 'alignment' => 'C',
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'text', 'Completion line');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 110, 'width' => 257,
            'fontsize' => 14, 'alignment' => 'C',
            'data' => json_encode(['text' => 'has successfully completed the course']),
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'coursename', 'Course name');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 125, 'width' => 257,
            'fontsize' => 18, 'alignment' => 'C',
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'date', 'Awarded');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 175, 'width' => 100,
            'fontsize' => 10, 'alignment' => 'L',
            'data' => json_encode(['dateitem' => 'awarded', 'prefix' => 'Awarded: ']),
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'code', 'Verification code');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 20, 'posy' => 185, 'width' => 100,
            'fontsize' => 8, 'colour' => '#666666', 'alignment' => 'L',
            'timemodified' => time(),
        ]);

        $id = self::create($certmanagerid, 'qrcode', 'QR code');
        $DB->update_record('certmanager_elements', (object)[
            'id' => $id, 'posx' => 250, 'posy' => 170, 'width' => 27, 'height' => 27,
            'timemodified' => time(),
        ]);
    }
}
