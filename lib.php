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
 * Library functions for Certification Manager plugin.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a new certmanager instance.
 *
 * @param mixed $data
 * @param mixed $mform
 */
function certmanager_add_instance($data, $mform) {
    global $DB, $USER;
    $data->timecreated = time();
    $data->timemodified = time();
    $data->usermodified = $USER->id;
    $id = $DB->insert_record('certmanager', $data);

    // Save required activities.
    certmanager_save_required_activities($id, $data);

    return $id;
}

/**
 * Update an existing certmanager instance.
 *
 * @param mixed $data
 * @param mixed $mform
 */
function certmanager_update_instance($data, $mform) {
    global $DB, $USER;
    $data->id = $data->instance;
    $data->timemodified = time();
    $data->usermodified = $USER->id;
    $DB->update_record('certmanager', $data);

    // Update required activities.
    $DB->delete_records('certmanager_required', ['certmanagerid' => $data->id]);
    certmanager_save_required_activities($data->id, $data);

    return true;
}

/**
 * Delete a certmanager instance and all its data.
 *
 * @param mixed $id
 */
function certmanager_delete_instance($id) {
    global $DB;
    if (!$DB->get_record('certmanager', ['id' => $id])) {
        return false;
    }

    // Delete all element files (image uploads etc.) first, then rows.
    $cm = get_coursemodule_from_instance('certmanager', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_certmanager', 'elementfiles');
    }

    $DB->delete_records('certmanager_elements', ['certmanagerid' => $id]);
    $DB->delete_records('certmanager_state', ['certmanagerid' => $id]);
    $DB->delete_records('certmanager_history', ['certmanagerid' => $id]);
    $DB->delete_records('certmanager_required', ['certmanagerid' => $id]);
    // Legacy tables (may not exist after v2 upgrade — ignore errors quietly).
    try {
        $DB->delete_records('certmanager_cert_design', ['certmanagerid' => $id]);
    } catch (\Throwable $e) {
        // Legacy table absent after v2 upgrade; nothing to clean up.
        unset($e);
    }
    \mod_certmanager\certificate_manager::delete_all_certificates($id);
    $DB->delete_records('certmanager', ['id' => $id]);
    return true;
}

/**
 * Declare which Moodle features this module supports.
 *
 * @param mixed $feature
 */
function certmanager_supports($feature) {
    switch ($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Serve files from the certmanager file areas.
 *
 * @param mixed $course
 * @param mixed $cm
 * @param mixed $context
 * @param mixed $filearea
 * @param mixed $args
 * @param mixed $forcedownload
 * @param array $options
 */
function certmanager_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    $validareas = ['elementfiles', 'certificates', 'background', 'logo', 'signature'];
    if (!in_array($filearea, $validareas)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_certmanager', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Save required activities for a certification instance.
 *
 * @param int $certmanagerid
 * @param object $data Form data containing activity_* fields
 */
function certmanager_save_required_activities($certmanagerid, $data) {
    global $DB;

    foreach ($data as $key => $val) {
        if (strpos($key, 'activity_') === 0 && $val) {
            $cmid = substr($key, 9);
            $rec = new stdClass();
            $rec->certmanagerid = $certmanagerid;
            $rec->cmid = (int) $cmid;
            $rec->timecreated = time();
            $DB->insert_record('certmanager_required', $rec);
        }
    }
}
