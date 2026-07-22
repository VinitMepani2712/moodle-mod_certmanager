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
 * Utilities for image-based elements
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element;

/**
 * Utilities for image-based elements: pulling files out of Moodle storage
 * into a temp file with the correct extension so TCPDF can render them.
 */
class image_helper {
    /**
     * Copy a stored image to a temp path with a real extension, and return
     * the temp path plus the TCPDF image type ("JPEG" / "PNG" / "GIF").
     *
     * Callers must call cleanup() once the PDF is written.
     *
     * @param int $contextid
     * @param string $filearea
     * @param int $itemid
     * @return array|null ['path'=>string, 'type'=>string] or null on failure.
     */
    public static function get_temp_image(int $contextid, string $filearea, int $itemid = 0): ?array {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $contextid,
            'mod_certmanager',
            $filearea,
            $itemid,
            'sortorder DESC, itemid ASC, filepath ASC, filename ASC',
            false
        );
        if (!$files) {
            return null;
        }
        $file = reset($files);
        if (!$file || $file->is_directory() || $file->get_filesize() == 0) {
            return null;
        }

        $mimetype = strtolower((string)$file->get_mimetype());
        $filename = $file->get_filename();
        $ext = null;
        if (strpos($mimetype, 'jpeg') !== false || strpos($mimetype, 'jpg') !== false) {
            $ext = 'jpg';
        } else if (strpos($mimetype, 'png') !== false) {
            $ext = 'png';
        } else if (strpos($mimetype, 'gif') !== false) {
            $ext = 'gif';
        } else {
            $dot = strrpos($filename, '.');
            if ($dot !== false) {
                $ext = strtolower(substr($filename, $dot + 1));
            }
        }

        $map = ['jpg' => 'JPEG', 'jpeg' => 'JPEG', 'png' => 'PNG', 'gif' => 'GIF'];
        if (!$ext || !isset($map[$ext])) {
            return null;
        }

        $base = tempnam(sys_get_temp_dir(), 'certel_');
        if ($base === false) {
            return null;
        }
        $temppath = $base . '.' . $ext;
        @unlink($base);
        if (!$file->copy_content_to($temppath)) {
            @unlink($temppath);
            return null;
        }
        return ['path' => $temppath, 'type' => $map[$ext]];
    }

    /**
     * URL for the (first) file in an element's file area, for HTML preview.
     * @param int $contextid The context ID
     * @param string $filearea The file area name
     * @param int $itemid The item ID
     * @return ?string The file URL or null
     */
    public static function get_url(int $contextid, string $filearea, int $itemid = 0): ?string {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $contextid,
            'mod_certmanager',
            $filearea,
            $itemid,
            'sortorder DESC, itemid ASC, filepath ASC, filename ASC',
            false
        );
        if (!$files) {
            return null;
        }
        $file = reset($files);
        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }

    /**
     * Delete an element's temporary file after rendering.
     * @param ?array $image The image array with 'path' key
     */
    public static function cleanup(?array $image): void {
        if ($image && !empty($image['path']) && file_exists($image['path'])) {
            @unlink($image['path']);
        }
    }
}
