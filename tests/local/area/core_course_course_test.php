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
 * Course custom fields test.
 *
 * @group      openlms
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \customfield_training\local\area\core_course_course
 */
final class core_course_course_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_get_category_select() {
        global $DB;

        $select = core_course_course::get_category_select('xx');
        $sql = "SELECT xx.*
                  FROM {customfield_category} xx
                 WHERE $select";
        $this->assertCount(0, $DB->get_records_sql($sql));

        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_group', 'area' => 'group']);
        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_group', 'area' => 'group']);
        $sql = "SELECT xx.*
                  FROM {customfield_category} xx
                 WHERE $select";
        $this->assertCount(1, $DB->get_records_sql($sql));
    }

    public function test_sync_area_completions() {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']);

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 10, 'customfield_field2' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 20]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 40]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user2->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course3->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course4->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();

        $DB->delete_records('customfield_training_completions', []);

        // Add completions.
        core_course_course::sync_area_completions();
        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(5, $completions);

        $this->assertTrue($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course3->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]));

        // No modifications.
        core_course_course::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('customfield_training_completions', [], 'id ASC'));

        // Removing of completions.
        $DB->delete_records('course_completions', ['course' => $course1->id, 'userid' => $user1->id]);
        core_course_course::sync_area_completions();
        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(3, $completions);
        $this->assertFalse($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('customfield_training_completions',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]));

        // Date sync.
        $DB->set_field('customfield_training_completions', 'timecompleted', '1', []);
        core_course_course::sync_area_completions();
        $completions2 = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertEquals($completions, $completions2);
    }

    public function test_observe_course_completed() {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']);

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 10, 'customfield_field2' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 20]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 40]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $this->assertCount(5, $DB->get_records('customfield_data', []));
        $this->assertCount(0, $DB->get_records('customfield_training_completions', []));

        $ccompletion = new \completion_completion(array('course' => $course4->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $this->assertCount(0, $DB->get_records('customfield_training_completions', []));

        $ccompletion = new \completion_completion(array('course' => $course3->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(1, $completions);
        $completion = reset($completions);
        $ccompletion = $DB->get_record('course_completions', ['course' => $course3->id, 'userid' => $user1->id]);
        $this->assertSame((string)$field1->get('id'), $completion->fieldid);
        $this->assertSame($course3->id, $completion->instanceid);
        $this->assertSame($user1->id, $completion->userid);
        $this->assertSame($ccompletion->timecompleted, $completion->timecompleted);
        $oldid = $completion->id;

        $ccompletion = new \completion_completion(array('course' => $course3->id, 'userid' => $user1->id));
        $ccompletion->timecompleted = null;
        $ccompletion->mark_complete();
        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(1, $completions);
        $completion = reset($completions);
        $ccompletion = $DB->get_record('course_completions', ['course' => $course3->id, 'userid' => $user1->id]);
        $this->assertSame($oldid, $completion->id);
        $this->assertSame((string)$field1->get('id'), $completion->fieldid);
        $this->assertSame($course3->id, $completion->instanceid);
        $this->assertSame($user1->id, $completion->userid);
        $this->assertSame($ccompletion->timecompleted, $completion->timecompleted);

        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user2->id));
        $ccompletion->mark_complete();
        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(3, $completions);

        core_course_course::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('customfield_training_completions', []));
    }

    public function test_observe_course_deleted() {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']);

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 10, 'customfield_field2' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 20]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 40]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user2->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course3->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course4->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();

        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(5, $completions);

        delete_course($course1, false);
        $this->assertFalse($DB->record_exists('course', ['id' => $course1->id]));

        $completions = $DB->get_records('customfield_training_completions', [], 'id ASC');
        $this->assertCount(1, $completions);

        core_course_course::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('customfield_training_completions', []));
    }
}
