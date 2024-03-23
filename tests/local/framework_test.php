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

/**
 * Training framework helper test.
 *
 * @group      openlms
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \customfield_training\local\framework
 */
final class framework_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_create() {
        $syscontext = \context_system::instance();

        $data = [
            'name' => 'Some framework',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $this->setCurrentTimeStart();
        $framework = framework::create($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame(null, $framework->idnumber);
        $this->assertSame('', $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame('0', $framework->public);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame('0', $framework->restrictedcompletion);
        $this->assertSame('0', $framework->archived);
        $this->assertTimeCurrent($framework->timecreated);

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $data = [
            'contextid' => (string)$categorycontext->id,
            'name' => 'Some framework 2',
            'idnumber' => 'f2',
            'requiredtraining' => '10',
            'description' => 'pokus',
            'public' => '1',
            'restrictedcompletion' => '1',
            'archived' => '1',
        ];
        $this->setCurrentTimeStart();
        $framework = framework::create($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['idnumber'], $framework->idnumber);
        $this->assertSame($data['description'], $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame($data['public'], $framework->public);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame($data['restrictedcompletion'], $framework->restrictedcompletion);
        $this->assertSame($data['archived'], $framework->archived);
        $this->assertTimeCurrent($framework->timecreated);

        try {
            $data = [
                'name' => 'Some framework 3',
                'idnumber' => 'f2',
                'contextid' => (string)$syscontext->id,
                'requiredtraining' => '101',
            ];
            framework::create($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework idnumber must be unique)', $e->getMessage());
        }

        try {
            $data = [
                'name' => 'Some framework 4',
                'contextid' => (string)$syscontext->id,
                'requiredtraining' => 0,
            ];
            framework::create($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }

        try {
            $data = [
                'name' => 'Some framework 4',
                'contextid' => (string)$syscontext->id,
                'requiredtraining' => -2,
            ];
            framework::create($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }
    }

    public function test_update() {
        $syscontext = \context_system::instance();
        $data = [
            'name' => 'Some framework',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $framework = framework::create($data);

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $data = [
            'id' => $framework->id,
            'contextid' => (string)$categorycontext->id,
            'name' => 'Some framework 2',
            'idnumber' => 'f2',
            'requiredtraining' => '10',
            'description' => 'pokus',
            'public' => '1',
            'restrictedcompletion' => '1',
            'archived' => '1',
        ];
        $framework = framework::update($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['idnumber'], $framework->idnumber);
        $this->assertSame($data['description'], $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame($data['public'], $framework->public);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame($data['restrictedcompletion'], $framework->restrictedcompletion);
        $this->assertSame($data['archived'], $framework->archived);

        $data = [
            'id' => $framework->id,
            'contextid' => (string)$categorycontext->id,
            'name' => 'Some framework 2',
            'idnumber' => 'f2',
            'requiredtraining' => '10',
            'description' => 'pokus',
            'public' => '1',
            'restrictedcompletion' => '0',
            'archived' => '0',
        ];
        $framework = framework::update($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['idnumber'], $framework->idnumber);
        $this->assertSame($data['description'], $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame($data['public'], $framework->public);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame($data['restrictedcompletion'], $framework->restrictedcompletion);
        $this->assertSame($data['archived'], $framework->archived);

        $data = [
            'name' => 'Some framework 2',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $framework2 = framework::create($data);

        try {
            $data = [
                'id' => $framework2->id,
                'idnumber' => 'f2',
            ];
            framework::update($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework idnumber must be unique)', $e->getMessage());
        }

        try {
            $data = [
                'id' => $framework2->id,
                'requiredtraining' => '0',
            ];
            framework::update($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }

        try {
            $data = [
                'id' => $framework2->id,
                'requiredtraining' => '-2',
            ];
            framework::update($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }
    }

    public function test_is_deletable() {
        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');
        /** @var \enrol_programs_generator $program1generator */
        $program1generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();
        $program1 = $program1generator->create_program();
        $program2 = $program1generator->create_program();

        $this->assertTrue(framework::is_deletable($framework1));
        $this->assertTrue(framework::is_deletable($framework2));

        $top = \enrol_programs\local\program::load_content($program1->id);
        $top->append_training($top, $framework1->id);

        $this->assertFalse(framework::is_deletable($framework1));
        $this->assertTrue(framework::is_deletable($framework2));

        $top = \enrol_programs\local\program::load_content($program2->id);
        $top->append_training($top, $framework2->id);

        $this->assertFalse(framework::is_deletable($framework1));
        $this->assertFalse(framework::is_deletable($framework2));

        \enrol_programs\local\program::delete_program($program1->id);

        $this->assertTrue(framework::is_deletable($framework1));
        $this->assertFalse(framework::is_deletable($framework2));
    }

    public function test_get_all_training_fields() {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']);

        $result = framework::get_all_training_fields();
        $this->assertArrayHasKey($field1->get('id'), $result);
        $this->assertArrayHasKey($field2->get('id'), $result);
        $this->assertCount(2, $result);

        $f1 = $DB->get_record('customfield_field', ['id' => $field1->get('id')]);
        $f1->component = 'core_course';
        $f1->area = 'course';
        $this->assertEquals($f1, $result[$f1->id]);
    }

    public function test_field_add() {
        global $DB;

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

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();

        $record1 = framework::field_add($framework1->id, $field1->get('id'));
        $this->assertSame($framework1->id, $record1->frameworkid);
        $this->assertSame((string)$field1->get('id'), $record1->fieldid);

        $record1x = framework::field_add($framework1->id, $field1->get('id'));
        $this->assertEquals($record1, $record1x);

        $record2 = framework::field_add($framework1->id, $field2->get('id'));
        $record3 = framework::field_add($framework2->id, $field1->get('id'));

        $this->assertCount(3, $DB->get_records('customfield_training_fields', []));

        try {
            framework::field_add(-10, $field2->get('id'));
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $e);
        }

        try {
            framework::field_add($framework1->id, -10);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (Invalid field: -10)', $e->getMessage());
        }

        try {
            framework::field_add($framework1->id, $field3->get('id'));
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (Invalid field: '. $field3->get('id') . ')', $e->getMessage());
        }

        $this->assertCount(3, $DB->get_records('customfield_training_fields', []));
    }

    public function test_field_remove() {
        global $DB;

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();

        $record1 = framework::field_add($framework1->id, $field1->get('id'));
        $record2 = framework::field_add($framework1->id, $field2->get('id'));
        $record3 = framework::field_add($framework2->id, $field1->get('id'));

        framework::field_remove($record1->frameworkid, $record1->fieldid);
        $this->assertCount(2, $DB->get_records('customfield_training_fields', []));
        framework::field_remove($record1->frameworkid, $record1->fieldid);
        $this->assertCount(2, $DB->get_records('customfield_training_fields', []));
        framework::field_remove($record2->frameworkid, $record2->fieldid);
        $this->assertCount(1, $DB->get_records('customfield_training_fields', []));
        framework::field_remove($record3->frameworkid, $record3->fieldid);
        $this->assertCount(0, $DB->get_records('customfield_training_fields', []));
    }

    public function test_delete() {
        global $DB;

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);

        $admin = get_admin();
        $site = get_site();
        $framework1 = $generator->create_framework();
        framework::field_add($framework1->id, $field1->get('id'));
        framework::field_add($framework1->id, $field2->get('id'));
        $framework2 = $generator->create_framework();
        framework::field_add($framework2->id, $field1->get('id'));
        $DB->insert_record('customfield_training_completions',
            ['fieldid' => $field1->get('id'), 'userid' => $admin->id, 'instanceid' => $site->id, 'timecompleted' => time()]);

        framework::delete($framework1->id);

        $this->assertFalse($DB->record_exists('customfield_training_frameworks', ['id' => $framework1->id]));
        $this->assertCount(0, $DB->get_records('customfield_training_fields', ['frameworkid' => $framework1->id]));
        $this->assertTrue($DB->record_exists('customfield_training_frameworks', ['id' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('customfield_training_fields', ['frameworkid' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('customfield_training_completions', []));

        framework::delete($framework1->id);

        $this->assertFalse($DB->record_exists('customfield_training_frameworks', ['id' => $framework1->id]));
        $this->assertCount(0, $DB->get_records('customfield_training_fields', ['frameworkid' => $framework1->id]));
        $this->assertTrue($DB->record_exists('customfield_training_frameworks', ['id' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('customfield_training_fields', ['frameworkid' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('customfield_training_completions', []));
    }

    public function test_is_area_compatible() {
        $this->assertTrue(framework::is_area_compatible('core_course', 'course'));
        $this->assertFalse(framework::is_area_compatible('core_course', 'group'));
    }
}
