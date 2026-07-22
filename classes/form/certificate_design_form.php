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
 * Certificate design form
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Form for designing a certificate layout.
 *
 * @package    mod_certmanager
 * @copyright  2026 Vinit Mepani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_design_form extends \moodleform {
    /**
     * Define the form fields.
     */
    protected function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];

        $mform->addElement('header', 'general', 'Certificate Template');

        $mform->addElement('text', 'certificatetitle', 'Certificate Title', ['size' => 60]);
        $mform->setType('certificatetitle', PARAM_TEXT);
        $mform->setDefault('certificatetitle', 'Certificate of Completion');
        $mform->addRule('certificatetitle', 'Required', 'required', null, 'client');

        $mform->addElement('textarea', 'awardtext', 'Award Text (Use {name} for learner name)', ['rows' => 4, 'cols' => 60]);
        $mform->setType('awardtext', PARAM_RAW);

        $mform->addElement('select', 'orientation', 'Page Orientation', [
            'L' => 'Landscape (297mm × 210mm)',
            'P' => 'Portrait (210mm × 297mm)',
        ]);
        $mform->setDefault('orientation', 'L');

        // Text Styling.
        $mform->addElement('header', 'styling', 'Text Styling');

        $fonts = [
            'helvetica' => 'Helvetica',
            'times' => 'Times New Roman',
            'courier' => 'Courier',
            'dejavu' => 'DejaVu',
        ];
        $mform->addElement('select', 'font', 'Font Family', $fonts);
        $mform->setDefault('font', 'helvetica');

        $mform->addElement('text', 'fontcolor', 'Font Color');
        $mform->setType('fontcolor', PARAM_NOTAGS);
        $mform->setDefault('fontcolor', '#000000');

        $mform->addElement('text', 'titlefontsize', 'Title Font Size (pt)', ['size' => 5]);
        $mform->setType('titlefontsize', PARAM_INT);
        $mform->setDefault('titlefontsize', 28);

        $mform->addElement('text', 'textfontsize', 'Body Font Size (pt)', ['size' => 5]);
        $mform->setType('textfontsize', PARAM_INT);
        $mform->setDefault('textfontsize', 12);

        // Images.
        $mform->addElement('header', 'images', 'Images & Signatures');

        $fileoptions = ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['image']];

        $mform->addElement('filemanager', 'backgroundimage', 'Background Image (JPG/PNG recommended)', null, $fileoptions);

        $mform->addElement('filemanager', 'logoimage', 'Logo / Institution Image (appears top-right)', null, $fileoptions);

        $mform->addElement('filemanager', 'signatureimage', 'Signature Image', null, $fileoptions);

        $mform->addElement('text', 'signatureline', 'Signature Line Text', ['size' => 60]);
        $mform->setType('signatureline', PARAM_TEXT);

        // Security.
        $mform->addElement('header', 'security', 'Security & Features');

        $mform->addElement('advcheckbox', 'showqrcode', 'Include QR Code');
        $mform->setDefault('showqrcode', 1);

        // Template saving.
        $mform->addElement('header', 'templates', 'Save as Template');

        $mform->addElement('advcheckbox', 'saveastemplate', 'Save this design as a template');

        $mform->addElement('text', 'templatename', 'Template Name', ['size' => 60]);
        $mform->setType('templatename', PARAM_TEXT);
        $mform->disabledIf('templatename', 'saveastemplate', 'notchecked');

        $mform->addElement('advcheckbox', 'templateshared', 'Make template available to all teachers');
        $mform->disabledIf('templateshared', 'saveastemplate', 'notchecked');

        // Hidden fields for element positions (mm on A4 Landscape: 297mm x 210mm)
        // Default to 0 = no custom positioning (element uses CSS default)
        // User drag will set non-zero values.
        $mform->addElement('hidden', 'title_x', '0');
        $mform->setType('title_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'title_y', '0');
        $mform->setType('title_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'award_x', '0');
        $mform->setType('award_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'award_y', '0');
        $mform->setType('award_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'logo_x', '0');
        $mform->setType('logo_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'logo_y', '0');
        $mform->setType('logo_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'signature_x', '0');
        $mform->setType('signature_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'signature_y', '0');
        $mform->setType('signature_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'certname_x', '0');
        $mform->setType('certname_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'certname_y', '0');
        $mform->setType('certname_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'signatureline_x', '0');
        $mform->setType('signatureline_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'signatureline_y', '0');
        $mform->setType('signatureline_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'dates_x', '0');
        $mform->setType('dates_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'dates_y', '0');
        $mform->setType('dates_y', PARAM_FLOAT);

        $mform->addElement('hidden', 'qr_x', '0');
        $mform->setType('qr_x', PARAM_FLOAT);

        $mform->addElement('hidden', 'qr_y', '0');
        $mform->setType('qr_y', PARAM_FLOAT);

        $this->add_action_buttons(true, 'Save Certificate Design');
    }

    /**
     * Validate the submitted form data.
     *
     * @param mixed $data
     * @param mixed $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['titlefontsize']) && ((int)$data['titlefontsize'] < 10 || (int)$data['titlefontsize'] > 72)) {
            $errors['titlefontsize'] = 'Font size must be between 10-72pt';
        }

        if (!empty($data['textfontsize']) && ((int)$data['textfontsize'] < 8 || (int)$data['textfontsize'] > 48)) {
            $errors['textfontsize'] = 'Font size must be between 8-48pt';
        }

        if (!empty($data['saveastemplate']) && empty($data['templatename'])) {
            $errors['templatename'] = 'Template name is required';
        }

        return $errors;
    }
}
