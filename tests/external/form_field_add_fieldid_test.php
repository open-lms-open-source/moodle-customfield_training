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

/**
 * Autocompletion for adding of fields to frameworks.
 *
 * @group      openlms
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 * @covers \customfield_training\external\form_field_add_fieldid
 */
final class form_field_add_fieldid_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_execute() {
        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field3']);
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['contextid' => $catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('customfield/training:manageframeworks', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $user1->id, $syscontext->id);
        role_assign($managerroleid, $user2->id, $catcontext->id);

        $this->setUser($user1);

        $result = form_field_add_fieldid::execute('', $framework1->id);
        $expected = [
            'notice' => null,
            'list' => [
                ['value' => (string)$field1->get('id'), 'label' => $field1->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field2->get('id'), 'label' => $field2->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field3->get('id'), 'label' => $field3->get('name') . ' <small>(core_course/course)</small>'],
            ],
        ];
        $this->assertSame($expected, $result);

        framework::field_add($framework2->id, $field2->get('id'));
        $result = form_field_add_fieldid::execute('', $framework2->id);
        $expected = [
            'notice' => null,
            'list' => [
                ['value' => (string)$field1->get('id'), 'label' => $field1->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field3->get('id'), 'label' => $field3->get('name') . ' <small>(core_course/course)</small>'],
            ],
        ];
        $this->assertSame($expected, $result);

        $this->setUser($user2);
        $result = form_field_add_fieldid::execute('', $framework2->id);
        $expected = [
            'notice' => null,
            'list' => [
                ['value' => (string)$field1->get('id'), 'label' => $field1->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field3->get('id'), 'label' => $field3->get('name') . ' <small>(core_course/course)</small>'],
            ],
        ];
        $this->assertSame($expected, $result);

        try {
            form_field_add_fieldid::execute('', $framework1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
    }

    public function test_get_label_callback() {
        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field3']);
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['contextid' => $catcontext->id]);

        $callback = form_field_add_fieldid::get_label_callback(['frameworkid' => $framework1->id]);
        $this->assertSame($field1->get('name'), $callback($field1->get('id')));
        $this->assertSame('Error', $callback(-1));
        $this->assertSame('Error', $callback($field4->get('id')));
    }

    public function test_validate_form_value() {
        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field3']);
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['contextid' => $catcontext->id]);

        framework::field_add($framework2->id, $field2->get('id'));

        $result = form_field_add_fieldid::validate_form_value(['frameworkid' => $framework1->id], $field1->get('id'));
        $this->assertNull($result);

        $result = form_field_add_fieldid::validate_form_value(['frameworkid' => $framework1->id], $field4->get('id'));
        $this->assertSame('Error', $result);

        $result = form_field_add_fieldid::validate_form_value(['frameworkid' => $framework2->id], $field2->get('id'));
        $this->assertSame('Error', $result);

        $result = form_field_add_fieldid::validate_form_value(['frameworkid' => $framework2->id], -1);
        $this->assertSame('Error', $result);
    }
}
