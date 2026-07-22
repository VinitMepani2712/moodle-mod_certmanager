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

/**
 * Evaluates whether a user's course completions satisfy a certification path.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class path_manager {
    /**
     * Check path satisfaction for a user.
     *
     * @param certification $cert Certification.
     * @param int $userid User id.
     * @param int $since Only count completions with timecompleted >= this (0 = any time).
     * @return array [bool satisfied, int progresspct]
     */
    public static function check(certification $cert, int $userid, int $since = 0): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $courses = $DB->get_records(
            'mod_certmanager_course',
            ['certid' => $cert->get('id')],
            'sortorder ASC'
        );
        if (empty($courses)) {
            return [false, 0];
        }

        $courseids = array_map(static fn($c) => (int) $c->courseid, $courses);
        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $completions = $DB->get_records_select(
            'course_completions',
            "userid = :userid AND course $insql AND timecompleted > 0 AND timecompleted >= :since",
            $inparams + ['userid' => $userid, 'since' => $since],
            '',
            'course, timecompleted'
        );

        $completed = 0;
        foreach ($courses as $pathcourse) {
            if (!isset($completions[$pathcourse->courseid])) {
                continue;
            }
            if ($pathcourse->mingrade !== null && $pathcourse->mingrade !== '') {
                $grade = grade_get_course_grade($userid, $pathcourse->courseid);
                if (!$grade || $grade->grade === null || $grade->grade < (float) $pathcourse->mingrade) {
                    continue;
                }
            }
            $completed++;
        }

        $required = $cert->get_required_count();
        $satisfied = $required > 0 && $completed >= $required;
        $progresspct = $required > 0 ? (int) min(100, floor($completed * 100 / $required)) : 0;
        return [$satisfied, $progresspct];
    }
}
