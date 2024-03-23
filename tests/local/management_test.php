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
 * Training management helper test.
 *
 * @group      openlms
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \customfield_training\local\management
 */
final class management_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_get_management_url() {
        global $DB;

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework3 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework4 = $generator->create_framework(['contextid' => $catcontext2->id]);

        $admin = get_admin();
        $guest = guest_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_assign($managerrole->id, $manager->id, $catcontext2->id);

        $viewer = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('customfield/training:viewframeworks', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $viewer->id, $catcontext1->id);

        $this->setUser(null);
        $this->assertNull(management::get_management_url());

        $this->setUser($guest);
        $this->assertNull(management::get_management_url());

        $this->setUser($admin);
        $expected = new \moodle_url('/customfield/field/training/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($manager);
        $expected = new \moodle_url('/customfield/field/training/management/index.php', ['contextid' => $catcontext2->id]);
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($viewer);
        $expected = new \moodle_url('/customfield/field/training/management/index.php', ['contextid' => $catcontext1->id]);
        $this->assertSame((string)$expected, (string)management::get_management_url());
    }

    public function test_fetch_frameworks() {
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $framework1 = $generator->create_framework(['name' => 'hokus']);
        $framework2 = $generator->create_framework(['idnumber' => 'pokus']);
        $framework3 = $generator->create_framework(['archived' => 1]);
        $framework4 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework5 = $generator->create_framework(['contextid' => $catcontext1->id, 'archived' => 1]);
        $framework6 = $generator->create_framework(['contextid' => $catcontext2->id]);

        $result = management::fetch_frameworks(null, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(4, $result['frameworks']);
        $this->assertSame(4, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework1->id, $frameworks);
        $this->assertArrayHasKey($framework2->id, $frameworks);
        $this->assertArrayHasKey($framework4->id, $frameworks);
        $this->assertArrayHasKey($framework6->id, $frameworks);

        $result = management::fetch_frameworks(null, false, 'hokus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['frameworks']);
        $this->assertSame(1, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework1->id, $frameworks);

        $result = management::fetch_frameworks(null, false, 'okus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['frameworks']);
        $this->assertSame(2, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework1->id, $frameworks);
        $this->assertArrayHasKey($framework2->id, $frameworks);

        $result = management::fetch_frameworks(null, true, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['frameworks']);
        $this->assertSame(2, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework3->id, $frameworks);
        $this->assertArrayHasKey($framework5->id, $frameworks);

        $result = management::fetch_frameworks($catcontext1, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['frameworks']);
        $this->assertSame(1, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework4->id, $frameworks);

        $result = management::fetch_frameworks(null, false, '', 1, 2, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['frameworks']);
        $this->assertSame(4, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework4->id, $frameworks);
        $this->assertArrayHasKey($framework6->id, $frameworks);

        $result = management::fetch_frameworks(null, false, '', 3, 1, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['frameworks']);
        $this->assertSame(4, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework6->id, $frameworks);
    }

    public function test_get_used_contexts_menu() {
        global $DB;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $category3 = $this->getDataGenerator()->create_category([]);
        $catcontext3 = \context_coursecat::instance($category3->id);

        $user = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user->id, $catcontext1);
        role_assign($managerrole->id, $user->id, $catcontext3);
        // Undo work hackery.
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability('moodle/category:viewcourselist', CAP_ALLOW, $managerrole->id, $syscontext->id);
        $coursecatcache = \cache::make('core', 'coursecat');
        $coursecatcache->purge();

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();
        $framework3 = $generator->create_framework();
        $framework4 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework5 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework6 = $generator->create_framework(['contextid' => $catcontext2->id]);

        $this->setAdminUser();
        $expected = [
            0 => 'All frameworks (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
        ];
        $contexts = management::get_used_contexts_menu($syscontext);
        $this->assertSame($expected, $contexts);

        $expected = [
            0 => 'All frameworks (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
            $catcontext3->id => $category3->name,
        ];
        $contexts = management::get_used_contexts_menu($catcontext3);
        $this->assertSame($expected, $contexts);

        $this->setUser($user);
        $coursecatcache->purge();

        $expected = [
            $catcontext1->id => $category1->name . ' (2)',
        ];
        $contexts = management::get_used_contexts_menu($catcontext1);
        $this->assertSame($expected, $contexts);

        $expected = [
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext3->id => $category3->name,
        ];
        $contexts = management::get_used_contexts_menu($catcontext3);
        $this->assertSame($expected, $contexts);
    }

    public function test_get_framework_search_query() {
        global $DB;

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $framework1 = $generator->create_framework(['name' => 'First framework', 'idnumber' => 'PRG1', 'description' => 'prvni popis']);
        $framework2 = $generator->create_framework(['name' => 'Second framework', 'idnumber' => 'PRG2', 'description' => 'druhy popis']);
        $framework3 = $generator->create_framework(['name' => 'Third framework', 'idnumber' => 'PR3', 'description' => 'treti popis', 'contextid' => $catcontext1->id]);

        list($search, $params) = management::get_framework_search_query(null, 'First', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, 'First', '');
        $frameworkids = $DB->get_fieldset_sql("SELECT * FROM {customfield_training_frameworks} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$framework1->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, 'PRG', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, 'popis', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id, $framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, '', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id, $framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, '', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, 'PR', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, 'PR', '');
        $frameworkids = $DB->get_fieldset_sql("SELECT * FROM {customfield_training_frameworks} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, 'PRG', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {customfield_training_frameworks} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([], $frameworkids);
    }

    public function test_setup_index_page() {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $framework1 = $generator->create_framework();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/customfield/field/training/management/index.php'),
            $syscontext,
            0
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/customfield/field/training/management/index.php'),
            $syscontext,
            $syscontext->id
        );
    }

    public function test_setup_framework_page() {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \customfield_training_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $framework1 = $generator->create_framework();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_framework_page(
            new \moodle_url('/customfield/field/training/management/new.php'),
            $syscontext,
            $framework1
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_framework_page(
            new \moodle_url('/customfield/field/training/management/new.php'),
            $syscontext,
            $framework1
        );
    }
}
