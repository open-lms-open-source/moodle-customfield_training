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

namespace customfield_training\table;

use stdClass;
use moodle_url;
use customfield_training\local\framework;

require_once($CFG->libdir . '/tablelib.php');

/**
 * All training frameworks.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class fields extends \table_sql {

    const DEFAULT_PERPAGE = 99999;
    protected $framework;

    public function __construct(moodle_url $url, stdClass $framework) {
        parent::__construct('customfield_training_fields');

        $this->framework = $framework;
        $page = optional_param('page', 0, PARAM_INT);
        $params = [];
        if ($page > 0) {
            $params['page'] = $page;
            $this->currpage = $page;
        }
        $baseurl = new moodle_url($url, $params);
        $this->define_baseurl($baseurl);
        $this->pagesize = self::DEFAULT_PERPAGE;

        $this->collapsible(false);
        $this->sortable(true, 'name', SORT_ASC);
        $this->pageable(true);
        $this->is_downloadable(false);

        $columns = [
            'name',
            'shortname',
            'component',
            'area',
            'actions',
        ];
        $headers = [
            get_string('name'),
            get_string('shortname'),
            get_string('component', 'customfield_training'),
            get_string('area', 'customfield_training'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->set_attribute('id', 'customfield_training_fields_table');

        foreach ($columns as $column) {
            if ($column !== 'name') {
                $this->no_sorting($column);
            }
        }

        $frameworkid = (int)$this->framework->id;

        // NOTE: for now only course custom fields.
        $sql = "SELECT cf.id, cf.name, cf.shortname, cc.component, cc.area
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cc ON cc.id = cf.categoryid AND (cc.component = 'core_course' AND cc.area = 'course')
                  JOIN {customfield_training_fields} tf ON tf.fieldid = cf.id
                 WHERE cf.type = 'training' AND tf.frameworkid = $frameworkid";
        $this->set_sql("*", "($sql) AS fields", "1=1", []);
    }

    /**
     * Display the framework name.
     *
     * @param stdClass $field
     * @return string html used to display the framework name
     */
    public function col_name(stdClass $field) {
        $name = format_string($field->name);
        return $name;
    }

    /**
     * Display the framework name.
     *
     * @param stdClass $field
     * @return string html used to display the framework name
     */
    public function col_shortname(stdClass $field) {
        $name = s($field->shortname);
        return $name;
    }

    /**
     * Display the framework public flag.
     *
     * @param stdClass $field
     * @return string
     */
    public function col_component(stdClass $field) {
        return $field->component;
    }

    /**
     * Display the fields list.
     *
     * @param stdClass $field
     * @return string
     */
    public function col_area(stdClass $field) {
        return $field->area;
    }

    /**
     * Display the action buttons.
     *
     * @param stdClass $field
     * @return string
     */
    public function col_actions(stdClass $field) {
        global $PAGE;

        /** @var \local_openlms\output\dialog_form\renderer $dialogformoutput */
        $dialogformoutput = $PAGE->get_renderer('local_openlms', 'dialog_form');

        $html = '';

        if (!$this->framework->archived && has_capability('customfield/training:manageframeworks', \context_system::instance())) {
            $url = new \moodle_url('/customfield/field/training/management/field_remove.php',
                ['frameworkid' => $this->framework->id, 'fieldid' => $field->id]);
            $button = new \local_openlms\output\dialog_form\icon($url, 'i/delete', get_string('field_remove', 'customfield_training'), 'moodle');
            $html .= $dialogformoutput->render($button);
        }

        return $html;
    }

    public function print_nothing_to_display() {
        // Get rid of ugly H2 heading.
        echo '<em>' . get_string('nothingtodisplay') . '</em>';
    }
}
