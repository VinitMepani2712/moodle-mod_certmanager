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
 * Edit form for a single certificate element
 *
 * @package     mod_certmanager
 * @copyright   2026 Vinit Mepani
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_certmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use mod_certmanager\element\base;

/**
 * Edit form for a single element. Common fields (position, size, style)
 * on top, then the element type adds its own fields.
 *
 * NOTE: We do NOT add hidden `id` or `cmid` fields here. The form's action URL
 * carries `?cmid=CMID&eid=EID` and edit_element.php reads them from the URL.
 * Previously adding a hidden `id` shadowed the URL param and broke Save.
 */
class edit_element_form extends \moodleform {
    /**
     * Define the form fields.
     */
    protected function definition() {
        $mform = $this->_form;
        /** @var base $element */
        $element = $this->_customdata['element'];
        $context = $this->_customdata['context'];

        // Type-specific fields (name/prefix/text/imagefile/etc).
        $element->add_form_fields($mform, $context);

        // Common visual fields.
        $mform->addElement('header', 'style', 'Styling');

        $fonts = ['helvetica' => 'Helvetica', 'times' => 'Times', 'courier' => 'Courier', 'dejavusans' => 'DejaVu Sans'];
        $mform->addElement('select', 'font', 'Font', $fonts);
        $mform->setDefault('font', $element->get_font());

        $mform->addElement('text', 'fontsize', 'Font size (pt)', ['size' => 5]);
        $mform->setType('fontsize', PARAM_INT);
        $mform->setDefault('fontsize', $element->get_fontsize());

        $mform->addElement('text', 'colour', 'Colour (#rrggbb)', ['size' => 10]);
        $mform->setType('colour', PARAM_NOTAGS);
        $mform->setDefault('colour', $element->get_colour());

        $mform->addElement(
            'select',
            'alignment',
            'Alignment',
            ['L' => 'Left', 'C' => 'Center', 'R' => 'Right']
        );
        $mform->setDefault('alignment', $element->get_alignment());

        $mform->addElement('header', 'geometry', 'Position & size (mm)');

        $mform->addElement('text', 'posx', 'X position');
        $mform->setType('posx', PARAM_FLOAT);
        $mform->setDefault('posx', $element->get_posx());

        $mform->addElement('text', 'posy', 'Y position');
        $mform->setType('posy', PARAM_FLOAT);
        $mform->setDefault('posy', $element->get_posy());

        $mform->addElement('text', 'width', 'Width (0 = auto)');
        $mform->setType('width', PARAM_FLOAT);
        $mform->setDefault('width', $element->get_width());

        $mform->addElement('text', 'height', 'Height (0 = auto)');
        $mform->setType('height', PARAM_FLOAT);
        $mform->setDefault('height', $element->get_height());

        $this->add_action_buttons(true, 'Save element');
    }

    /**
     * Validate the submitted form data.
     *
     * @param mixed $data
     * @param mixed $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data['fontsize']) && ((int)$data['fontsize'] < 1 || (int)$data['fontsize'] > 200)) {
            $errors['fontsize'] = 'Font size must be between 1 and 200';
        }
        return $errors;
    }
}
