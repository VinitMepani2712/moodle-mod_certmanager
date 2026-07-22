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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_certmanager;

/**
 * Certification instance wrapper.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification {
    /** @var \stdClass Certification record from database. */
    private $record;

    /**
     * Constructor.
     *
     * @param int $id Certification instance ID
     */
    public function __construct(int $id) {
        global $DB;
        $this->record = $DB->get_record('certmanager', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Get a field value from the certification record.
     *
     * @param string $field Field name
     * @return mixed Field value
     */
    public function get(string $field) {
        if (!isset($this->record->$field)) {
            throw new \coding_exception("Unknown field: $field");
        }
        return $this->record->$field;
    }

    /**
     * Get the full record object.
     *
     * @return \stdClass
     */
    public function get_record() {
        return $this->record;
    }
}
