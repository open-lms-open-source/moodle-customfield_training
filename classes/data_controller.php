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

namespace customfield_training;

use customfield_training\local\framework;

/**
 * Data class for training field
 *
 * @package   customfield_training
 * @copyright 2024 Open LMS (https://www.openlms.net/)
 * @author    Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_controller extends \core_customfield\data_controller {

    /**
     * Return the name of the field where the information is stored
     * @return string
     */
    public function datafield() : string {
        return 'intvalue';
    }

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function instance_form_definition(\MoodleQuickForm $mform) {
        $elementname = $this->get_form_element_name();
        $required = $this->get_field()->get_configdata_property('required');

        $mform->addElement('text', $elementname, $this->get_field()->get_formatted_name(), 'size=5');
        $mform->setType($elementname, PARAM_RAW);

        $category = $this->get_field()->get_category();
        if (!framework::is_area_compatible($category->get('component'), $category->get('area'))) {
            $warning = get_string('error_incompatiblearea', 'customfield_training');
            $warning = '<div class="alert alert-warning">' . $warning . '</div>';
            $mform->addElement('static', $elementname.'warning', '', $warning);
        }

        if ($required) {
            $mform->addRule($elementname, null, 'required', null, 'client');
        }
    }

    /**
     * Validates data for this field.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function instance_form_validation(array $data, array $files) : array {
        $errors = parent::instance_form_validation($data, $files);
        $elementname = $this->get_form_element_name();
        $required = $this->get_field()->get_configdata_property('required');

        if (!array_key_exists($elementname, $data)) {
            // This should not happen.
            return $errors;
        }

        if ($data[$elementname] !== '' && $data[$elementname] !== null) {
            if (!is_number($data[$elementname]) || $data[$elementname] < 0) {
                $errors[$elementname] = get_string('error');
            }
        }

        if ($required && empty($data[$elementname])) {
            $errors[$elementname] = get_string('required');
        }

        return $errors;
    }

    /**
     * Called before setting the data, will format float.
     *
     * @param \stdClass $instance custom field instance.
     * @return void
     */
    public function instance_form_before_set_data(\stdClass $instance) {
        $value = $this->get_value();
        $instance->{$this->get_form_element_name()} = $value;
    }

    /**
     * Returns the value as it is stored in the database.
     *
     * @return mixed
     */
    public function get_value() {
        // Do NOT return default value here, we want the real database value used in fast DB queries.

        if (!$this->get('id')) {
            return null;
        }

        return $this->get($this->datafield());
    }

    /**
     * There is intentionally no support for default values.
     *
     * @return mixed
     */
    public function get_default_value() {
        return null;
    }

    /**
     * Saves the data coming from form
     *
     * @param \stdClass $datanew data coming from the form
     */
    public function instance_form_save(\stdClass $datanew) {
        $elementname = $this->get_form_element_name();

        if (!property_exists($datanew, $elementname)) {
            return;
        }

        $value = $datanew->$elementname;
        if ($value === '') {
            $value = null;
        } else if ($value !== null) {
            if ($value < 0) {
                throw new \invalid_parameter_exception('training amount value cannot be negative');
            }
            $value = (int)$value;
            if ($value == 0) {
                $value = null;
            }
        }

        $this->data->set($this->datafield(), $value);
        $this->data->set('value', (string)$value);
        $this->save();
    }

    /**
     * Returns value in a human-readable format
     *
     * @return string|null value or null if empty
     */
    public function export_value() {
        // Do not use parent::export_value() here, we do not want any nulls here.
        return $this->get_value();
    }
}
