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

namespace mod_certmanager\event;

/**
 * Event fired when a user's certification lapses after the grace period.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification_lapsed extends \core\event\base {
    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'mod_certmanager_state';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcertificationlapsed', 'mod_certmanager');
    }

    /**
     * Non-localised event description.
     *
     * @return string
     */
    public function get_description() {
        return "The certification with id '{$this->other['certid']}' changed state " .
            "(a user's certification lapses after the grace period) for the user with id '$this->relateduserid'.";
    }

    /**
     * URL relevant to this event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/certmanager/report.php', ['certid' => $this->other['certid']]);
    }

    /**
     * Object id mapping for backup/restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'mod_certmanager_state', 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Other-field mapping for backup/restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return ['certid' => ['db' => 'mod_certmanager_cert', 'restore' => \core\event\base::NOT_MAPPED]];
    }

    /**
     * Validate required custom data.
     *
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['certid'])) {
            throw new \coding_exception('The \'certid\' value must be set in other.');
        }
    }
}
