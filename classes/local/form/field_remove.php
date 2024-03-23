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

/**
 * Remove field from training framework.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_remove extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];
        $field = $this->_customdata['field'];

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $mform->addElement('hidden', 'fieldid');
        $mform->setType('fieldid', PARAM_INT);
        $mform->setDefault('fieldid', $field->id);

        $name = format_string($field->name);
        $mform->addElement('static', 'strfield', get_string('field', 'customfield_training'), $name);

        $this->add_action_buttons(true, get_string('field_remove', 'customfield_training'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
