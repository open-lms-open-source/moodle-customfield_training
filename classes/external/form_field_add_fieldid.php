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

namespace customfield_training\external;

use customfield_training\local\framework;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Provides list of candidates for adding fields to framework.
 *
 * @package     customfield_training
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_field_add_fieldid extends \local_openlms\external\form_autocomplete_field {
    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return false;
    }

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(\PARAM_RAW, 'The search query', \VALUE_REQUIRED),
            'frameworkid' => new external_value(\PARAM_INT, 'Framework id', \VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request.
     * @param int $frameworkid The framework.
     * @return array
     */
    public static function execute(string $query, int $frameworkid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(),
            ['query' => $query, 'frameworkid' => $frameworkid]);
        $query = $params['query'];
        $frameworkid = $params['frameworkid'];

        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $frameworkid], '*', \MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($framework->contextid);
        self::validate_context($context);
        \require_capability('customfield/training:manageframeworks', $context);

        $allfields = framework::get_all_training_fields();
        $current = $DB->get_records_menu('customfield_training_fields', ['frameworkid' => $framework->id], '', 'fieldid, id');

        $list = [];
        $notice = null;

        if (!$allfields) {
            $notice = get_string('error_notrainingfields', 'customfield_training');
        }

        foreach ($allfields as $field) {
            if (isset($current[$field->id])) {
                continue;
            }

            if ($query) {
                if (!str_contains($field->name, $query) && !str_contains($field->shortname, $query)) {
                    continue;
                }
            }

            $name = \format_string($field->name);

            $list[] = [
                'value' => $field->id,
                'label' => "$name <small>($field->component/$field->area)</small>",
            ];
        }
        return [
            'notice' => $notice,
            'list' => $list,
        ];
    }

    /**
     * Return function that return label for given value.
     *
     * @param array $arguments
     * @return callable
     */
    public static function get_label_callback(array $arguments): callable {
        return function($value) use ($arguments): string {
            $allfields = framework::get_all_training_fields();
            $name = $allfields[$value]->name ?? \get_string('error');
            return \format_string($name);
        };
    }

    /**
     * @param array $arguments
     * @param $value
     * @return string|null error message, NULL means value is ok
     */
    public static function validate_form_value(array $arguments, $value): ?string {
        global $DB;

        if (!$value) {
            return null;
        }

        $allfields = framework::get_all_training_fields();

        if (!isset($allfields[$value])) {
            return \get_string('error');
        }

        if ($DB->record_exists('customfield_training_fields',
            ['frameworkid' => $arguments['frameworkid'], 'fieldid' => $value])) {

            return \get_string('error');
        }

        return null;
    }
}
