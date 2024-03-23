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
 * Update training framework.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_update extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $editoroptions = $this->_customdata['editoroptions'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('idnumber'), 'maxlength="100" size="50"');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        $options = $this->get_category_options($data->contextid);
        $mform->addElement('autocomplete', 'contextid', get_string('context', 'role'), $options);
        $mform->addRule('contextid', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'public', get_string('public', 'customfield_training'), ' ');

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 3], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('text', 'requiredtraining', get_string('requiredtraining', 'customfield_training'));
        $mform->setType('requiredtraining', PARAM_INT);
        $mform->addRule('requiredtraining', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'restrictedcompletion', get_string('restrictedcompletion', 'customfield_training'), ' ');

        $mform->addElement('advcheckbox', 'archived', get_string('archived', 'customfield_training'), ' ');

        $this->add_action_buttons(true, get_string('framework_update', 'customfield_training'));

        $this->set_data($data);
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $olddata = $this->_customdata['data'];

        if (trim($data['idnumber']) !== '') {
            if ($DB->record_exists_select('customfield_training_frameworks', "LOWER(idnumber) = LOWER(?) AND id <> ?", [$data['idnumber'], $data['id']])) {
                $errors['idnumber'] = get_string('error');
            }
        }

        if ($data['requiredtraining'] <= 0) {
            $errors['requiredtraining'] = get_string('error');
        }

        $context = \context::instance_by_id($data['contextid'], IGNORE_MISSING);
        if (!$context) {
            $errors['contextid'] = get_string('required');
        } else if ($olddata->contextid != $data['contextid']) {
            if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
                $errors['contextid'] = get_string('error');
            } else if (!has_capability('customfield/training:manageframeworks', $context)) {
                $errors['contextid'] = get_string('error');
            }
        }

        return $errors;
    }

    protected function get_category_options(int $currentcontextid): array {
        $displaylist = \core_course_category::make_categories_list('customfield/training:manageframeworks');
        $options = array();
        $syscontext = \context_system::instance();
        if (has_capability('customfield/training:manageframeworks', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        foreach ($displaylist as $cid => $name) {
            $context = \context_coursecat::instance($cid);
            $options[$context->id] = $name;
        }
        if (!isset($options[$currentcontextid])) {
            $context = \context::instance_by_id($currentcontextid, MUST_EXIST);
            $options[$context->id] = $syscontext->get_context_name();
        }
        return $options;
    }
}
