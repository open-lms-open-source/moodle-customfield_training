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

namespace customfield_training\local\form;

use customfield_training\external\form_field_add_fieldid;

/**
 * Add field to training framework.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_add extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $arguments = ['frameworkid' => $framework->id];
        form_field_add_fieldid::add_form_element(
            $mform, $arguments, 'fieldid', get_string('field', 'customfield_training'));
        $mform->addRule('fieldid', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('field_add', 'customfield_training'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $framework = $this->_customdata['framework'];

        $arguments = ['frameworkid' => $framework->id];
        $error = form_field_add_fieldid::validate_form_value($arguments, $data['fieldid']);
        if ($error !== null) {
            $errors['fieldid'] = $error;
        }

        return $errors;
    }
}
