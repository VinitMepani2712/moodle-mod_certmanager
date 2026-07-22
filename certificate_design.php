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
 * Certificate design and template management page.
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_certmanager\template_manager;

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$templateid = optional_param('templateid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('certmanager', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certmanager = $DB->get_record('certmanager', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

$context = context_module::instance($cm->id);
$pageurl = new moodle_url('/mod/certmanager/certificate_design.php', ['id' => $id]);
$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($certmanager->name) . ' - Certificate Design');
$PAGE->set_heading(format_string($certmanager->name));

$PAGE->requires->css(new moodle_url('/mod/certmanager/style/certificate_design.css'));
$PAGE->requires->js(new moodle_url('/mod/certmanager/javascript/certificate_design.js'));

// Handle template actions.
if ($action === 'loadtemplate' && $templateid > 0) {
    $template = template_manager::get_template($templateid);
    if ($template) {
        $design = template_manager::apply_template($template);
    } else {
        throw new \moodle_exception('errtemplatenotfound', 'mod_certmanager');
    }
} else if ($action === 'exporttemplate' && $templateid > 0) {
    $json = template_manager::export_template($templateid);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="certificate_template_' . $templateid . '.json"');
    echo $json;
    exit;
} else if ($action === 'deletetemplate' && $templateid > 0 && confirm_sesskey()) {
    template_manager::delete_template($templateid);
    redirect($pageurl, get_string('templatedeleted', 'mod_certmanager'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Load existing design.
$design = $DB->get_record('certmanager_cert_design', ['certmanagerid' => $certmanager->id]);
if (!$design) {
    $design = new stdClass();
    $design->certmanagerid = $certmanager->id;
    $design->certificatetitle = 'Certificate of Completion';
    $design->awardtext = 'This certifies that {name} has successfully completed';
    $design->orientation = 'L';
    $design->font = 'helvetica';
    $design->fontcolor = '#000000';
    $design->titlefontsize = 28;
    $design->textfontsize = 12;
    $design->showqrcode = 1;
}

$form = new \mod_certmanager\form\certificate_design_form(
    $pageurl,
    ['certmanagerid' => $certmanager->id, 'context' => $context]
);

// Prepare filemanager draft areas.
if (isset($design->id)) {
    file_prepare_standard_filemanager(
        $design,
        'backgroundimage',
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']],
        $context,
        'mod_certmanager',
        'background',
        0
    );

    file_prepare_standard_filemanager(
        $design,
        'logoimage',
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']],
        $context,
        'mod_certmanager',
        'logo',
        0
    );

    file_prepare_standard_filemanager(
        $design,
        'signatureimage',
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']],
        $context,
        'mod_certmanager',
        'signature',
        0
    );
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/certmanager/view.php', ['id' => $id]));
}

if ($data = $form->get_data()) {
    $now = time();

    // Handle file uploads FIRST.
    $data = file_postupdate_standard_filemanager(
        $data,
        'backgroundimage',
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']],
        $context,
        'mod_certmanager',
        'background',
        0
    );

    $data = file_postupdate_standard_filemanager(
        $data,
        'logoimage',
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']],
        $context,
        'mod_certmanager',
        'logo',
        0
    );

    $data = file_postupdate_standard_filemanager(
        $data,
        'signatureimage',
        ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']],
        $context,
        'mod_certmanager',
        'signature',
        0
    );

    // NOW build the record.
    $record = new stdClass();
    $record->certmanagerid = $certmanager->id;  // IMPORTANT: Set this!
    $record->certificatetitle = $data->certificatetitle;
    $record->awardtext = isset($data->awardtext) ? $data->awardtext : '';
    $record->orientation = $data->orientation;
    $record->font = $data->font;
    $record->fontcolor = $data->fontcolor;
    $record->titlefontsize = (int)$data->titlefontsize;
    $record->textfontsize = (int)$data->textfontsize;
    $record->signatureline = isset($data->signatureline) ? $data->signatureline : '';
    $record->showqrcode = empty($data->showqrcode) ? 0 : 1;

    // Persist free-drag element positions (mm on the A4 page).
    $positionfields = [
        'title_x', 'title_y', 'award_x', 'award_y', 'certname_x', 'certname_y',
        'signatureline_x', 'signatureline_y', 'logo_x', 'logo_y',
        'signature_x', 'signature_y', 'dates_x', 'dates_y', 'qr_x', 'qr_y',
    ];
    foreach ($positionfields as $pf) {
        $record->$pf = isset($data->$pf) ? (float)$data->$pf : 0;
    }

    $record->usermodified = $USER->id;
    $record->timemodified = $now;

    // Get file IDs from the file areas.
    $fs = get_file_storage();

    $bgfiles = $fs->get_area_files($context->id, 'mod_certmanager', 'background', 0, 'timecreated', false);
    if ($bgfiles) {
        $bgfile = reset($bgfiles);
        $record->backgroundimageid = $bgfile->get_id();
    } else {
        $record->backgroundimageid = null;
    }

    $logofiles = $fs->get_area_files($context->id, 'mod_certmanager', 'logo', 0, 'timecreated', false);
    if ($logofiles) {
        $logofile = reset($logofiles);
        $record->logoimageid = $logofile->get_id();
    } else {
        $record->logoimageid = null;
    }

    $sigfiles = $fs->get_area_files($context->id, 'mod_certmanager', 'signature', 0, 'timecreated', false);
    if ($sigfiles) {
        $sigfile = reset($sigfiles);
        $record->signatureimageid = $sigfile->get_id();
    } else {
        $record->signatureimageid = null;
    }

    // Save or update.
    if ($design && isset($design->id)) {
        $record->id = $design->id;
        $DB->update_record('certmanager_cert_design', $record);
    } else {
        $record->timecreated = $now;
        $DB->insert_record('certmanager_cert_design', $record);
    }

    // Save as template if requested.
    if (!empty($data->saveastemplate) && !empty($data->templatename)) {
        template_manager::save_as_template($record, $data->templatename, !empty($data->templateshared));
    }

    redirect(
        new moodle_url('/mod/certmanager/view.php', ['id' => $id]),
        'Certificate design saved successfully',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($design && isset($design->id)) {
    $form->set_data($design);
}

echo $OUTPUT->header();

// Template selector.
echo html_writer::start_div('template-selector mb-3');
echo html_writer::tag('h4', 'Templates');

$templates = template_manager::get_user_templates($USER->id);

if (!empty($templates)) {
    echo html_writer::start_div('btn-group', ['role' => 'group']);
    foreach ($templates as $template) {
        $isactive = isset($currenttemplate) && $currenttemplate->id == $template->id;
        $btnclass = 'btn btn-sm ' . ($isactive ? 'btn-primary' : 'btn-outline-primary');
        $loadurl = new moodle_url($pageurl, ['action' => 'loadtemplate', 'templateid' => $template->id]);
        echo html_writer::link($loadurl, $template->name, ['class' => $btnclass]);
    }
    echo html_writer::end_div();
} else {
    echo html_writer::div('No templates yet. Save your first design as a template!', 'alert alert-info');
}

echo html_writer::end_div();

// Split-screen container.
echo html_writer::start_div('certificate-design-container');

// Left side: Form.
echo html_writer::start_div('design-form-panel');
echo html_writer::tag('h3', 'Design Certificate');
$form->display();
echo html_writer::end_div();

// Right side: Live Preview.
echo html_writer::start_div('design-preview-panel');
echo html_writer::tag('h3', 'Live Preview');
echo html_writer::div('Updates as you design', 'text-muted small mb-2');

echo html_writer::start_div('certificate-preview', ['id' => 'certPreview']);
echo html_writer::start_div('preview-a4-landscape', ['id' => 'previewContainer']);

echo html_writer::div('', 'preview-background', ['id' => 'previewBg']);
echo html_writer::img('', 'Logo', ['id' => 'previewLogo', 'class' => 'preview-logo', 'style' => 'display:none;']);
echo html_writer::tag('div', 'Certificate of Completion', ['class' => 'preview-title', 'id' => 'previewTitle']);
echo html_writer::tag(
    'div',
    'This certifies that {name} has successfully completed',
    ['class' => 'preview-award-text', 'id' => 'previewAwardText']
);
echo html_writer::tag('div', 'Course Name', ['class' => 'preview-cert-name', 'id' => 'previewCertName']);
echo html_writer::img('', 'Signature', ['id' => 'previewSignature', 'class' => 'preview-signature', 'style' => 'display:none;']);
echo html_writer::tag('div', 'Director of Training', ['class' => 'preview-signature-line', 'id' => 'previewSignatureLine']);
echo html_writer::tag('div', 'Awarded: Today<br>Expires: 365 days', ['class' => 'preview-dates', 'id' => 'previewDates']);
echo html_writer::tag('div', '[QR]', ['class' => 'preview-qr', 'id' => 'previewQR']);

echo html_writer::end_div(); // Preview-a4-landscape.
echo html_writer::end_div(); // Certificate-preview.
echo html_writer::end_div(); // Design-preview-panel.

echo html_writer::end_div(); // Certificate-design-container.

$bgurl = '';
$logourl = '';
$sigurl = '';

if (!empty($design->backgroundimageid)) {
    $bgfile = $DB->get_record('files', ['id' => $design->backgroundimageid]);
    if ($bgfile && $bgfile->filename !== '.') {
        $fs = get_file_storage();
        $mfile = $fs->get_file_by_id($design->backgroundimageid);
        if ($mfile) {
            $bgurl = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_certmanager',
                'background',
                0,
                '/',
                $mfile->get_filename()
            )->out(false);
        }
    }
}

if (!empty($design->logoimageid)) {
    $logofile = $DB->get_record('files', ['id' => $design->logoimageid]);
    if ($logofile && $logofile->filename !== '.') {
        $fs = get_file_storage();
        $mfile = $fs->get_file_by_id($design->logoimageid);
        if ($mfile) {
            $logourl = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_certmanager',
                'logo',
                0,
                '/',
                $mfile->get_filename()
            )->out(false);
        }
    }
}

if (!empty($design->signatureimageid)) {
    $sigfile = $DB->get_record('files', ['id' => $design->signatureimageid]);
    if ($sigfile && $sigfile->filename !== '.') {
        $fs = get_file_storage();
        $mfile = $fs->get_file_by_id($design->signatureimageid);
        if ($mfile) {
            $sigurl = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_certmanager',
                'signature',
                0,
                '/',
                $mfile->get_filename()
            )->out(false);
        }
    }
}

// Pass design data to JavaScript.
$designjson = json_encode([
    'title' => $design->certificatetitle ?? 'Certificate of Completion',
    'awardtext' => $design->awardtext ?? '',
    'font' => $design->font ?? 'helvetica',
    'fontcolor' => $design->fontcolor ?? '#000000',
    'titlesize' => $design->titlefontsize ?? 28,
    'bodysize' => $design->textfontsize ?? 12,
    'signature' => $design->signatureline ?? '',
    'showqr' => $design->showqrcode ?? 1,
    'orientation' => $design->orientation ?? 'L',
    'bgurl' => $bgurl,
    'logourl' => $logourl,
    'sigurl' => $sigurl,
    'title_x' => isset($design->title_x) ? (float)$design->title_x : null,
    'title_y' => isset($design->title_y) ? (float)$design->title_y : null,
    'award_x' => isset($design->award_x) ? (float)$design->award_x : null,
    'award_y' => isset($design->award_y) ? (float)$design->award_y : null,
    'certname_x' => isset($design->certname_x) ? (float)$design->certname_x : null,
    'certname_y' => isset($design->certname_y) ? (float)$design->certname_y : null,
    'signatureline_x' => isset($design->signatureline_x) ? (float)$design->signatureline_x : null,
    'signatureline_y' => isset($design->signatureline_y) ? (float)$design->signatureline_y : null,
    'logo_x' => isset($design->logo_x) ? (float)$design->logo_x : null,
    'logo_y' => isset($design->logo_y) ? (float)$design->logo_y : null,
    'signature_x' => isset($design->signature_x) ? (float)$design->signature_x : null,
    'signature_y' => isset($design->signature_y) ? (float)$design->signature_y : null,
    'dates_x' => isset($design->dates_x) ? (float)$design->dates_x : null,
    'dates_y' => isset($design->dates_y) ? (float)$design->dates_y : null,
    'qr_x' => isset($design->qr_x) ? (float)$design->qr_x : null,
    'qr_y' => isset($design->qr_y) ? (float)$design->qr_y : null,
]);
echo html_writer::tag('script', "var designData = " . $designjson . ";", ['type' => 'text/javascript']);


echo $OUTPUT->footer();

/**
 * Get file instance by ID
 * @param int $fileid The file ID
 * @return ?\stored_file The file object or null
 */
function get_file_instance_by_id($fileid) {
    global $DB;
    $file = $DB->get_record('files', ['id' => $fileid]);
    if ($file) {
        $fs = get_file_storage();
        return $fs->get_file_by_hash($file->pathnamehash);
    }
    return null;
}
