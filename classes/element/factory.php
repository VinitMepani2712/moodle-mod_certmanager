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
 * Element factory and registry
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\element;

/**
 * Element factory / registry.
 *
 * Maps element type strings ("text", "image", …) to their concrete classes,
 * and instantiates them from database records.
 */
class factory {
    /** @var array<string,string> Type => class map. */
    private static $types = [
        'text'          => \mod_certmanager\element\type\text::class,
        'studentname'   => \mod_certmanager\element\type\studentname::class,
        'coursename'    => \mod_certmanager\element\type\coursename::class,
        'certificatename' => \mod_certmanager\element\type\certificatename::class,
        'date'          => \mod_certmanager\element\type\date::class,
        'image'         => \mod_certmanager\element\type\image::class,
        'logo'          => \mod_certmanager\element\type\logo::class,
        'background'    => \mod_certmanager\element\type\background::class,
        'qrcode'        => \mod_certmanager\element\type\qrcode::class,
        'signatureline' => \mod_certmanager\element\type\signatureline::class,
        'border'        => \mod_certmanager\element\type\border::class,
        'code'          => \mod_certmanager\element\type\code::class,
    ];

    /**
     * Get the map of element type keys to their class names.
     *
     * @return array<string,string>
     */
    public static function get_types(): array {
        return self::$types;
    }

    /**
     * Menu of types for a select control: [type => display name].
     *
     * @return array<string,string>
     */
    public static function get_type_menu(): array {
        $menu = [];
        foreach (self::$types as $key => $class) {
            $menu[$key] = call_user_func([$class, 'get_display_name']);
        }
        asort($menu);
        return $menu;
    }

    /**
     * Instantiate an element from a DB record.
     *
     * @param \stdClass $record
     * @return base|null
     */
    public static function instance(\stdClass $record): ?base {
        $type = $record->element ?? '';
        if (!isset(self::$types[$type])) {
            return null;
        }
        $class = self::$types[$type];
        return new $class($record);
    }

    /**
     * Instantiate all elements for an activity, in sort order.
     *
     * @param int $certmanagerid
     * @return base[]
     */
    public static function get_elements($certmanagerid): array {
        global $DB;
        $rows = $DB->get_records(
            'certmanager_elements',
            ['certmanagerid' => $certmanagerid],
            'sortorder ASC, id ASC'
        );
        $result = [];
        foreach ($rows as $row) {
            $inst = self::instance($row);
            if ($inst) {
                $result[] = $inst;
            }
        }
        return $result;
    }
}
