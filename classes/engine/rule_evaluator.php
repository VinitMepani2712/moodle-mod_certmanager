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

namespace mod_certmanager\engine;

use mod_certmanager\certification;
use stdClass;

/**
 * Evaluates dynamic assignment rules (cohort membership, profile fields).
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_evaluator {
    /** @var int Rule type: cohort membership. */
    const TYPE_COHORT = 1;

    /** @var int Rule type: user profile field. */
    const TYPE_PROFILEFIELD = 2;

    /** @var string[] Core user fields supported by profile-field rules. */
    const CORE_FIELDS = ['department', 'institution', 'city'];

    /** @var rule_evaluator|null Singleton instance. */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return rule_evaluator
     */
    public static function instance(): rule_evaluator {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Apply all active cohort rules that reference the given cohort to one user.
     *
     * @param int $cohortid Cohort id.
     * @param int $userid User id.
     * @return void
     */
    public function apply_cohort_rules(int $cohortid, int $userid): void {
        global $DB;
        $rules = $DB->get_records(
            'mod_certmanager_rule',
            ['ruletype' => self::TYPE_COHORT, 'active' => 1]
        );
        foreach ($rules as $rule) {
            $config = json_decode($rule->configdata);
            if (empty($config->cohortid) || (int) $config->cohortid !== $cohortid) {
                continue;
            }
            $cert = new certification($rule->certid);
            if ($cert->get('archived')) {
                continue;
            }
            assignment_manager::assign($cert, $userid, (int) $rule->id);
        }
    }

    /**
     * Apply all active profile-field rules to one user.
     *
     * @param int $userid User id.
     * @return void
     */
    public function apply_profile_rules(int $userid): void {
        global $DB;
        $rules = $DB->get_records(
            'mod_certmanager_rule',
            ['ruletype' => self::TYPE_PROFILEFIELD, 'active' => 1]
        );
        if (empty($rules)) {
            return;
        }
        $user = \core_user::get_user($userid);
        if (!$user || $user->deleted) {
            return;
        }
        foreach ($rules as $rule) {
            $config = json_decode($rule->configdata);
            if (!$config || !$this->profile_matches($user, $config)) {
                continue;
            }
            $cert = new certification($rule->certid);
            if ($cert->get('archived')) {
                continue;
            }
            assignment_manager::assign($cert, $userid, (int) $rule->id);
        }
    }

    /**
     * Nightly reconciliation: assign every matching user for every active rule.
     *
     * @return int Number of new assignments created.
     */
    public function sync_all(): int {
        global $DB;
        $created = 0;
        $rules = $DB->get_records('mod_certmanager_rule', ['active' => 1]);
        foreach ($rules as $rule) {
            $cert = new certification($rule->certid);
            if ($cert->get('archived')) {
                continue;
            }
            $config = json_decode($rule->configdata);
            if (!$config) {
                continue;
            }
            foreach ($this->matching_userids($rule, $config) as $userid) {
                if (assignment_manager::assign($cert, (int) $userid, (int) $rule->id)) {
                    $created++;
                }
            }
        }
        return $created;
    }

    /**
     * Resolve the user ids currently matching one rule.
     *
     * @param stdClass $rule Rule record.
     * @param stdClass $config Decoded configdata.
     * @return int[] User ids.
     */
    private function matching_userids(stdClass $rule, stdClass $config): array {
        global $DB;

        if ((int) $rule->ruletype === self::TYPE_COHORT) {
            if (empty($config->cohortid)) {
                return [];
            }
            $sql = 'SELECT u.id
                      FROM {user} u
                      JOIN {cohort_members} cm ON cm.userid = u.id
                     WHERE cm.cohortid = :cohortid AND u.deleted = 0 AND u.suspended = 0';
            return array_keys($DB->get_records_sql($sql, ['cohortid' => (int) $config->cohortid]));
        }

        if ((int) $rule->ruletype === self::TYPE_PROFILEFIELD) {
            if (empty($config->field) || !isset($config->value)) {
                return [];
            }
            if (in_array($config->field, self::CORE_FIELDS, true)) {
                $sql = 'SELECT u.id FROM {user} u
                         WHERE u.deleted = 0 AND u.suspended = 0 AND ' .
                       $DB->sql_equal('u.' . $config->field, ':val', false);
                return array_keys($DB->get_records_sql($sql, ['val' => $config->value]));
            }
            $sql = 'SELECT u.id
                      FROM {user} u
                      JOIN {user_info_data} d ON d.userid = u.id
                      JOIN {user_info_field} f ON f.id = d.fieldid
                     WHERE u.deleted = 0 AND u.suspended = 0
                       AND f.shortname = :shortname AND ' .
                   $DB->sql_equal('d.data', ':val', false);
            return array_keys($DB->get_records_sql(
                $sql,
                ['shortname' => $config->field, 'val' => $config->value]
            ));
        }
        return [];
    }

    /**
     * Does one user's profile match a profile-field rule config?
     *
     * @param stdClass $user Full user record.
     * @param stdClass $config Decoded configdata with field and value.
     * @return bool
     */
    private function profile_matches(stdClass $user, stdClass $config): bool {
        global $CFG;
        if (empty($config->field) || !isset($config->value)) {
            return false;
        }
        if (in_array($config->field, self::CORE_FIELDS, true)) {
            $actual = $user->{$config->field} ?? '';
        } else {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $custom = profile_user_record($user->id, false);
            $actual = $custom->{$config->field} ?? '';
        }
        return \core_text::strtolower(trim((string) $actual))
            === \core_text::strtolower(trim((string) $config->value));
    }
}
