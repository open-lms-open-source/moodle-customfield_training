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

namespace customfield_training\local\area;

/**
 * Custom field area base.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class core_course_course extends base {
    public static function get_category_select(string $alias): string {
        return "$alias.component = 'core_course' AND $alias.area = 'course'";
    }

    public static function sync_area_completions(): void {
        global $DB;

        // Add completions.
        $sql = "INSERT INTO {customfield_training_completions}
                       (fieldid, instanceid, userid, timecompleted)

                SELECT DISTINCT cd.fieldid, cd.instanceid, cc.userid, cc.timecompleted
                  FROM {course_completions} cc
                  JOIN {customfield_data} cd ON cd.instanceid = cc.course AND cd.intvalue > 0
                  JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'training'
                  JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                  JOIN {user} u ON u.id = cc.userid AND u.deleted = 0 AND u.confirmed = 1   
             LEFT JOIN {customfield_training_completions} ctc ON ctc.fieldid = cd.fieldid AND ctc.instanceid = cd.instanceid AND ctc.userid = cc.userid
                 WHERE ctc.id IS NULL
              ORDER BY cc.timecompleted ASC";
        $DB->execute($sql);

        // Remove completions for non-existent course completions.
        $sql = "DELETE
                  FROM {customfield_training_completions}
                 WHERE EXISTS (

                    SELECT 'x'
                      FROM {customfield_data} cd
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'training'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {customfield_training_completions}.fieldid = cf.id

                 ) AND NOT EXISTS (

                    SELECT cc.id
                      FROM {course_completions} cc
                      JOIN {customfield_data} cd ON cd.instanceid = cc.course
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'training'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {customfield_training_completions}.fieldid = cf.id
                           AND {customfield_training_completions}.instanceid = cd.instanceid
                           AND {customfield_training_completions}.userid = cc.userid

                 )";
        $DB->execute($sql);

        // Sync completion dates.
        $sql = "UPDATE {customfield_training_completions} ctc
                   SET timecompleted = (

                        SELECT cc.timecompleted
                          FROM {course_completions} cc
                          JOIN {customfield_data} cd ON cd.instanceid = cc.course
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'training'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                         WHERE ctc.fieldid = cf.id AND ctc.instanceid = cd.instanceid AND ctc.userid = cc.userid

                   )
                 WHERE EXISTS (
                     
                        SELECT cc.id
                          FROM {course_completions} cc
                          JOIN {customfield_data} cd ON cd.instanceid = cc.course
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'training'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                         WHERE ctc.fieldid = cf.id AND ctc.instanceid = cd.instanceid AND ctc.userid = cc.userid
                               AND ctc.timecompleted <> cc.timecompleted
                     
                 )
        ";
        $DB->execute($sql);
    }

    public static function observe_course_completed(\core\event\course_completed $event): void {
        global $DB;

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        // NOTE: do not check course_completions.reaggregate here!
        $completion = $event->get_record_snapshot('course_completions', $event->objectid);

        $sql = "SELECT cf.*, ctc.id AS ctcid
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                  JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = :courseid AND cd.intvalue > 0
             LEFT JOIN {customfield_training_completions} ctc ON ctc.fieldid = cf.id AND ctc.instanceid = cd.instanceid AND ctc.userid = :userid
                 WHERE cf.type = 'training'
              ORDER BY cd.id ASC";
        $params = ['courseid' => $courseid, 'userid' => $userid];

        $inserted = [];
        $fields = $DB->get_records_sql($sql, $params);
        foreach ($fields as $field) {
            if ($field->ctcid) {
                $DB->set_field('customfield_training_completions',
                    'timecompleted', $completion->timecompleted, ['id' => $field->ctcid]);
            } else {
                $record = (object)[
                    'fieldid' => $field->id,
                    'instanceid' => $courseid,
                    'userid' => $userid,
                    'timecompleted' => $completion->timecompleted,
                ];
                $inserted[] = (int)$DB->insert_record('customfield_training_completions', $record);
            }
        }

        if ($inserted) {
            $inserted = implode(',', $inserted);
            $sql = "SELECT DISTINCT tf.frameworkid
                      FROM {customfield_training_fields} tf
                      JOIN {customfield_training_frameworks} tfw ON tfw.id = tf.frameworkid
                     WHERE tfw.archived = 0 AND tf.fieldid IN ($inserted)";
            $frameworkids = $DB->get_fieldset_sql($sql);
            if ($frameworkids) {
                // This should trigger things like program completion recalculation when user completes course.
                $hook = new \customfield_training\hook\completion_updated($userid, $frameworkids);
                \core\hook\manager::get_instance()->dispatch($hook);
            }
        }
    }

    public static function observe_course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        if (!$DB->record_exists('customfield_field', ['type' => 'training'])) {
            return;
        }

        $params = ['courseid' => $event->courseid];

        $sql = "DELETE
                  FROM {customfield_training_completions}
                 WHERE instanceid = :courseid AND EXISTS (

                    SELECT 'x'
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {customfield_training_completions}.fieldid = cf.id
                           AND cf.type = 'training'

                 )";
        $DB->execute($sql, $params);
    }
}
