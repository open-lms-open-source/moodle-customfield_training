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
class field_controller extends \core_customfield\field_controller {
    /**
     * Plugin type text
     */
    const TYPE = 'training';

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_definition(\MoodleQuickForm $mform) {
        $category = $this->get_category();
        if (!framework::is_area_compatible($category->get('component'), $category->get('area'))) {
            $warning = get_string('error_incompatiblearea', 'customfield_training');
            $warning = '<div class="alert alert-warning">' . $warning . '</div>';
            $mform->addElement('static', 'warningtraining', '', $warning);
        }
    }

    /**
     * Delete a field and all associated data
     *
     * Plugins may override it if it is necessary to delete related data (such as files)
     *
     * Not that the delete() method from data_controller is not called here.
     *
     * @return bool
     */
    public function delete() : bool {
        global $DB;

        $fieldid = $this->get('id');

        $DB->delete_records('customfield_training_completions', ['fieldid' => $fieldid]);
        $DB->delete_records('customfield_training_fields', ['fieldid' => $fieldid]);

        return parent::delete();
    }
}
