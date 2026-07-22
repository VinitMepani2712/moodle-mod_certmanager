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

namespace mod_certmanager;

/**
 * Manages certificate generation, storage, and retrieval.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_manager {
    /**
     * Generate a certificate PDF for the user and store it against the
     * cert record. Idempotent-ish: regenerates and replaces on repeat calls.
     *
     * @param int $certmanagerid
     * @param int $userid
     * @param int $timecertified
     * @param int $timeexpires
     * @return bool True on success.
     */
    public static function generate_certificate($certmanagerid, $userid, $timecertified, $timeexpires) {
        global $DB;

        $certmanager = $DB->get_record('certmanager', ['id' => $certmanagerid], '*', MUST_EXIST);
        if (empty($certmanager->enablecertificate)) {
            return false;
        }

        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user || $user->deleted) {
            return false;
        }

        $course = $DB->get_record('course', ['id' => $certmanager->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('certmanager', $certmanagerid, $course->id);
        $context = \context_module::instance($cm->id);

        // Reuse existing cert issue record (keep verification code + hash stable) if present.
        $existing = $DB->get_record(
            'certmanager_certificates',
            ['certmanagerid' => $certmanagerid, 'userid' => $userid]
        );

        $cert = new \stdClass();
        $cert->certmanagerid = $certmanagerid;
        $cert->userid = $userid;
        // Keep plaintext code for PDF display.
        $cert->code = $existing ? $existing->code : self::generate_code();
        // Hash the code for secure database storage.
        $cert->codehash = hash('sha256', strtoupper($cert->code));
        $cert->timecertified = $timecertified;
        $cert->timeexpires = $timeexpires;
        $cert->timemodified = time();

        try {
            $generator = new certificate_generator();
            $pdfcontent = $generator->generate($certmanager, $user, $cert);

            if (!$pdfcontent || strlen($pdfcontent) === 0) {
                throw new \Exception('PDF generation returned empty content');
            }

            $filename = 'certificate_' . $userid . '_' . $timecertified . '.pdf';

            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'mod_certmanager', 'certificates', $userid);

            $filerecord = (object) [
                'contextid' => $context->id,
                'component' => 'mod_certmanager',
                'filearea' => 'certificates',
                'itemid' => $userid,
                'filepath' => '/',
                'filename' => $filename,
                'timecreated' => time(),
                'timemodified' => time(),
                'userid' => $userid,
                'author' => fullname($user),
                'license' => 'allrightsreserved',
                'source' => 'Certification Manager',
            ];
            $file = $fs->create_file_from_string($filerecord, $pdfcontent);

            $now = time();
            if ($existing) {
                $existing->fileid = (int)$file->get_id();
                $existing->timecertified = $timecertified;
                $existing->timeexpires = $timeexpires;
                $existing->timemodified = $now;
                // Ensure codehash is set (for existing records that may not have it).
                if (empty($existing->codehash) && !empty($existing->code)) {
                    $existing->codehash = hash('sha256', strtoupper($existing->code));
                }
                $DB->update_record('certmanager_certificates', $existing);
            } else {
                $cert->fileid = (int)$file->get_id();
                $cert->timecreated = $now;
                $DB->insert_record('certmanager_certificates', $cert);
            }

            return true;
        } catch (\Throwable $e) {
            debugging('certmanager generation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \Exception('Certificate generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve the stored certificate PDF for a user.
     *
     * @param int $certmanagerid
     * @param int $userid
     * @return \stored_file|null
     */
    public static function get_certificate_file($certmanagerid, $userid) {
        global $DB;

        $rec = $DB->get_record(
            'certmanager_certificates',
            ['certmanagerid' => $certmanagerid, 'userid' => $userid]
        );
        if (!$rec || empty($rec->fileid)) {
            return null;
        }
        $fs = get_file_storage();
        return $fs->get_file_by_id($rec->fileid);
    }

    /**
     * Delete all certificate files + records for an activity.
     * @param int $certmanagerid The certificate manager activity ID
     */
    public static function delete_all_certificates($certmanagerid) {
        global $DB;

        $recs = $DB->get_records('certmanager_certificates', ['certmanagerid' => $certmanagerid]);
        $fs = get_file_storage();
        foreach ($recs as $rec) {
            if (!empty($rec->fileid)) {
                $file = $fs->get_file_by_id($rec->fileid);
                if ($file) {
                    $file->delete();
                }
            }
        }
        $DB->delete_records('certmanager_certificates', ['certmanagerid' => $certmanagerid]);
    }

    /**
     * Generate a random 12-character verification code.
     */
    private static function generate_code(): string {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No 0/O/I/1 confusion.
        $len = 12;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}
