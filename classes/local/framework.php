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

namespace customfield_training\local;

use stdClass;

/**
 * Framework helper class.
 *
 * @package   customfield_training
 * @copyright 2024 Open LMS (https://www.openlms.net/)
 * @author    Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework {

    /**
     * Create new training framework.
     *
     * @param array $data
     * @return stdClass
     */
    public static function create(array $data): stdClass {
        global $DB;

        $data = (object)$data;

        $record = new stdClass();

        $context = \context::instance_by_id($data->contextid);
        if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
            throw new \coding_exception('training framework contextid must be a system or course category');
        }
        $record->contextid = $context->id;

        $record->name = trim($data->name ?? '');
        if ($record->name === '') {
            throw new \invalid_parameter_exception('framework name cannot be empty');
        }
        $record->idnumber = trim($data->idnumber ?? '');
        if ($record->idnumber === '') {
            $record->idnumber = null;
        } else {
            if ($DB->record_exists_select('customfield_training_frameworks', "LOWER(idnumber) = LOWER(?)", [$record->idnumber])) {
                throw new \invalid_parameter_exception('framework idnumber must be unique');
            }
        }
        if (isset($data->description_editor)) {
            $record->description = $data->description_editor['text'];
            $record->descriptionformat = $data->description_editor['format'];
        } else {
            $record->description = $data->description ?? '';
            $record->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
        }

        $record->restrictedcompletion = (int)($data->restrictedcompletion ?? 0);
        if ($record->restrictedcompletion !== 0 && $record->restrictedcompletion !== 1) {
            throw new \invalid_parameter_exception('framework restrictedcompletion must be 1 or 0');
        }
        
        $record->public = (int)($data->public ?? 0);
        if ($record->public !== 0 && $record->public !== 1) {
            throw new \invalid_parameter_exception('framework public must be 1 or 0');
        }

        $record->requiredtraining = (int)$data->requiredtraining;
        if ($record->requiredtraining <= 0) {
            throw new \invalid_parameter_exception('framework requiredtraining must be positive integer');
        }

        $record->archived = (int)($data->archived ?? 0); // New frameworks should not be archived unless testing.
        if ($record->archived !== 0 && $record->archived !== 1) {
            throw new \invalid_parameter_exception('framework archived must be 1 or 0');
        }

        $record->timecreated = time();

        $trans = $DB->start_delegated_transaction();

        $id = $DB->insert_record('customfield_training_frameworks', $record);
        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $id]);

        $trans->allow_commit();

        return $framework;
    }

    /**
     * Update framework.
     *
     * @param array $data
     * @return stdClass
     */
    public static function update(array $data): stdClass {
        global $DB;

        $data = (object)$data;
        $oldrecord = $DB->get_record('customfield_training_frameworks', ['id' => $data->id], '*', MUST_EXIST);

        $record = clone($oldrecord);

        if (isset($data->contextid) && $data->contextid != $oldrecord->contextid) {
            // Cohort was moved to another context.
            $context = \context::instance_by_id($data->contextid);
            if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
                throw new \coding_exception('program contextid must be a system or course category');
            }
            $record->contextid = $context->id;
        } else {
            $context = \context::instance_by_id($record->contextid);
        }

        if (property_exists($data, 'name')) {
            $record->name = trim($data->name ?? '');
            if ($record->name === '') {
                throw new \invalid_parameter_exception('framework name cannot be empty');
            }
        }
        if (property_exists($data, 'idnumber')) {
            $record->idnumber = trim($data->idnumber ?? '');
            if ($record->idnumber === '') {
                $record->idnumber = null;
            } else {
                $select = "id <> ? AND LOWER(idnumber) = LOWER(?)";
                if ($DB->record_exists_select('customfield_training_frameworks', $select, [$record->id, $record->idnumber])) {
                    throw new \invalid_parameter_exception('framework idnumber must be unique');
                }
            }
        }
        if (property_exists($data, 'description_editor')) {
            $data->description = $data->description_editor['text'];
            $data->descriptionformat = $data->description_editor['format'];
            $editoroptions = self::get_description_editor_options($oldrecord->contextid);
            $data = file_postupdate_standard_editor($data, 'description', $editoroptions, $editoroptions['context'],
                'customfield_training', 'description', $data->id);
        }
        if (property_exists($data, 'description')) {
            $record->description = (string)$data->description;
            $record->descriptionformat = $data->descriptionformat ?? $record->descriptionformat;
        }
        if (property_exists($data, 'restrictedcompletion')) {
            $record->restrictedcompletion = (int)$data->restrictedcompletion;
            if ($record->restrictedcompletion !== 0 && $record->restrictedcompletion !== 1) {
                throw new \invalid_parameter_exception('framework restrictedcompletion must be 1 or 0');
            }
        }
        if (property_exists($data, 'public')) {
            $record->public = (int)$data->public;
            if ($record->public !== 0 && $record->public !== 1) {
                throw new \invalid_parameter_exception('framework public must be 1 or 0');
            }
        }
        if (property_exists($data, 'requiredtraining')) {
            $record->requiredtraining = (int)$data->requiredtraining;
            if ($record->requiredtraining <= 0) {
                throw new \invalid_parameter_exception('framework requiredtraining must be positive integer');
            }
        }
        if (property_exists($data, 'archived')) {
            $record->archived = (int)$data->archived;
            if ($record->archived !== 0 && $record->archived !== 1) {
                throw new \invalid_parameter_exception('framework archived must be 1 or 0');
            }
        }

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('customfield_training_frameworks', $record);
        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $record->id]);

        $trans->allow_commit();

        // NOTE: programs will be updated later via cron

        return $framework;
    }

    /**
     * Get all available training fields.
     *
     * @return array array of field records with extra component and area property taken from category
     */
    public static function get_all_training_fields(): array {
        global $DB;

        $classnames = \customfield_training\local\area\base::get_area_classes();
        $select = [];
        foreach ($classnames as $classname) {
            $select[] = '(' . $classname::get_category_select('cc') . ')';
        }
        $select = '(' . implode(' OR ', $select) . ')';

        $sql = "SELECT cf.*, cc.component, cc.area
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cc ON cc.id = cf.categoryid
                 WHERE cf.type = 'training' AND $select
              ORDER BY cf.name ASC, cc.component ASC, cc.area ASC";
        return $DB->get_records_sql($sql);
    }

    public static function field_add(int $frameworkid, int $fieldid): stdClass {
        global $DB;

        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $frameworkid], '*', MUST_EXIST);
        $allfields = self::get_all_training_fields();
        if (!isset($allfields[$fieldid])) {
            throw new \invalid_parameter_exception('Invalid field: ' . $fieldid);
        }

        $record = $DB->get_record('customfield_training_fields', ['frameworkid' => $framework->id, 'fieldid' => $fieldid]);
        if ($record) {
            return $record;
        }

        $record = (object)[
            'frameworkid' => $framework->id,
            'fieldid' => $fieldid,
        ];
        $record->id = $DB->insert_record('customfield_training_fields', $record);
        return $DB->get_record('customfield_training_fields', ['id' => $record->id], '*', MUST_EXIST);
    }

    public static function field_remove(int $frameworkid, int $fieldid): void {
        global $DB;

        $DB->delete_records('customfield_training_fields',
            ['frameworkid' => $frameworkid, 'fieldid' => $fieldid]);
    }


    public static function is_deletable(\stdClass $framework): bool {

        $hook = new \customfield_training\hook\framework_usage($framework->id);
        \core\hook\manager::get_instance()->dispatch($hook);

        if ($hook->get_usage()) {
            return false;
        }

        return true;
    }

    /**
     * Delete framework.
     *
     * NOTE: this does not check self::is_deletable().
     *
     * @param int $frameworkid
     */
    public static function delete(int $frameworkid): void {
        global $DB;

        $record = $DB->get_record('customfield_training_frameworks', ['id' => $frameworkid]);
        if (!$record) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('customfield_training_fields', ['frameworkid' => $record->id]);
        $DB->delete_records('customfield_training_frameworks', ['id' => $record->id]);

        $trans->allow_commit();
    }

    /**
     * Options for editing of framework descriptions.
     *
     * @return array
     */
    public static function get_description_editor_options(): array {
        $context = \context_system::instance();
        return ['maxfiles' => 0, 'context' => $context];
    }

    public static function is_area_compatible(string $component, string $area): bool {
        $classname = area\base::get_area_class($component, $area);
        return ($classname !== null);
    }
}
