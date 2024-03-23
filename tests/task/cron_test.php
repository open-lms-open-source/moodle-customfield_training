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

namespace customfield_training\task;

/**
 * Cron test.
 *
 * @group      openlms
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \customfield_training\task\cron
 */
final class cron_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_get_name() {
        $cron = new cron();
        $cron->get_name();
    }

    public function test_execute() {
        $cron = new cron();
        ob_start();
        $cron->execute();
        $output = ob_get_clean();
        $this->assertSame('', $output);

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

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
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user2->id));
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(array('course' => $course2->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();

        $cron = new cron();
        ob_start();
        $cron->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString('customfield_training\local\area\core_course_course::sync_area_completions', $output);
    }
}