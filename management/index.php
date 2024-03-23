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

/**
 * List of all training frameworks.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use customfield_training\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require_once('../../../../config.php');
require_once("$CFG->libdir/adminlib.php");

$contextid = optional_param('contextid', 0, PARAM_INT);
$archived = optional_param('archived', 0, PARAM_BOOL);
$page = optional_param('page', 0, PARAM_INT);
$searchquery = optional_param('search', '', PARAM_RAW);
$sort = optional_param('sort', 'name', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$perpage = 25;

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

require_login();
require_capability('customfield/training:viewframeworks', $context);

if ($context->contextlevel == CONTEXT_SYSTEM) {
    $category = null;
} else if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', ['id' => $context->instanceid], '*', MUST_EXIST);
} else {
    throw new moodle_exception('invalidcontext');
}

$pageparams = [];
if ($page > 0) {
    $pageparams['page'] = $page;
}
if (trim($searchquery) !== '') {
    $pageparams['search'] = $searchquery;
}
if ($contextid) {
    $pageparams['contextid'] = $contextid;
}
if ($archived) {
    $pageparams['archived'] = 1;
}
if ($sort !== 'name') {
    $pageparams['sort'] = $sort;
}
if ($dir !== 'ASC') {
    $pageparams['dir'] = $dir;
}

$currenturl = new moodle_url('/customfield/field/training/management/index.php', $pageparams);

management::setup_index_page($currenturl, $context, $contextid);

/** @var \local_openlms\output\dialog_form\renderer $dialogformoutput */
$dialogformoutput = $PAGE->get_renderer('local_openlms', 'dialog_form');

echo $OUTPUT->header();

// Allow category switching.

$contextoptions = management::get_used_contexts_menu($context);

echo '<div class="framework-category-selector float-right">';
$changecontexturl = new moodle_url($currenturl);
$changecontexturl->remove_all_params();
echo $OUTPUT->single_select($changecontexturl, 'contextid', $contextoptions, $contextid, [], 'frameworkcategoryselect',
    ['label' => '<span class="accesshide">' . get_string('selectcategory', 'customfield_training') . '</span>']);
echo '</div>';

$taburl = new moodle_url($currenturl);
$taburl->remove_params(['archived']);
$taburl->remove_params(['search']);
$tabs[] = new tabobject('active', $taburl, get_string('frameworksactive', 'customfield_training'));
$tabs[] = new tabobject('archived', new moodle_url($taburl, ['archived' => 1]), get_string('frameworksarchived', 'customfield_training'));
echo $OUTPUT->render(new \tabtree($tabs, ($archived ? 'archived' : 'active')));

if (!$archived && has_capability('customfield/training:manageframeworks', $context)) {
    $addurl = new moodle_url('/customfield/field/training/management/framework_create.php', ['contextid' => $context->id]);
    $addbutton = new local_openlms\output\dialog_form\button($addurl, get_string('framework_create', 'customfield_training'));
    $addbutton->set_after_submit($addbutton::AFTER_SUBMIT_REDIRECT);
    $button = $dialogformoutput->render($addbutton);
    echo '<div class="buttons float-right">';
    echo $button;
    echo '</div>';
}

// Add search form.
$data = [
    'action' => new moodle_url('/customfield/field/training/management/index.php'),
    'inputname' => 'search',
    'searchstring' => get_string('search', 'search'),
    'query' => $searchquery,
    'hiddenfields' => [
        (object)['name' => 'contextid', 'value' => $contextid],
        (object)['name' => 'archived', 'value' => $archived],
        (object)['name' => 'sort', 'value' => $sort],
        (object)['name' => 'dir', 'value' => $dir],
    ],
    'extraclasses' => 'mb-3'
];
echo $OUTPUT->render_from_template('core/search_input', $data);

if ($sort === 'idnumber') {
    $orderby = 'idnumber';
} else {
    $orderby = 'name';
}
if ($dir === 'ASC') {
    $orderby .= ' ASC';
} else {
    $orderby .= ' DESC';
}

if ($contextid) {
    $frameworksinfo = management::fetch_frameworks($context, (bool)$archived, $searchquery, $page, $perpage, $orderby);
} else {
    $frameworksinfo = management::fetch_frameworks(null, (bool)$archived, $searchquery, $page, $perpage, $orderby);
}

echo $OUTPUT->paging_bar($frameworksinfo['totalcount'], $page, $perpage, $currenturl);

$data = [];

foreach ($frameworksinfo['frameworks'] as $framework) {
    $pcontext = context::instance_by_id($framework->contextid, MUST_EXIST);
    $row = [];
    if (!$contextid) {
        $row[] = html_writer::link(new moodle_url('/customfield/field/training/management/index.php',
            ['contextid' => $pcontext->id]), $pcontext->get_context_name(false));
    }
    $name = format_string($framework->name);
    if (has_capability('customfield/training:viewframeworks', $pcontext)) {
        $detailurl = new moodle_url('/customfield/field/training/management/framework.php', ['id' => $framework->id]);
        $name = html_writer::link($detailurl, $name);
    }

    $row[] = $name;
    $row[] = s($framework->idnumber);
    $row[] = format_text($framework->description, $framework->descriptionformat, ['context' => $pcontext]);
    $row[] = $DB->count_records('customfield_training_fields', ['frameworkid' => $framework->id]);
    $row[] = ($framework->public ? get_string('yes') : get_string('no'));
    $row[] = $framework->requiredtraining;
    $row[] = ($framework->restrictedcompletion ? get_string('yes') : get_string('no'));
    $data[] = $row;
}

if (!$frameworksinfo['totalcount']) {
    echo get_string('error_noframeworks', 'customfield_training');

} else {
    $columns = [];

    $column = get_string('name');
    $columndir = ($dir === "ASC" ? "DESC" : "ASC");
    $columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
    $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
        ['class' => 'iconsort']);
    $changeurl = new moodle_url($currenturl);
    $changeurl->param('sort', 'name');
    $changeurl->param('dir', $columndir);
    $column = html_writer::link($changeurl, $column);
    if ($sort === 'name') {
        $column .= $columnicon;
    }
    $columns[] = $column;

    $column = get_string('idnumber');
    $columndir = ($dir === "ASC" ? "DESC" : "ASC");
    $columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
    $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
        ['class' => 'iconsort']);
    $changeurl = new moodle_url($currenturl);
    $changeurl->param('sort', 'idnumber');
    $changeurl->param('dir', $columndir);
    $column = html_writer::link($changeurl, $column);
    if ($sort === 'idnumber') {
        $column .= $columnicon;
    }
    $columns[] = $column;

    $columns[] = get_string('description');
    $columns[] = get_string('fields', 'customfield_training');
    $columns[] = get_string('public', 'customfield_training');
    $columns[] = get_string('requiredtraining', 'customfield_training');
    $columns[] = get_string('restrictedcompletion', 'customfield_training');

    $table = new html_table();
    $table->head = $columns;
    if (!$contextid) {
        array_unshift($table->head, get_string('category'));
    }
    $table->id = 'management_frameworks';
    $table->attributes['class'] = 'admintable generaltable';
    $table->data = $data;
    echo html_writer::table($table);
}

echo $OUTPUT->paging_bar($frameworksinfo['totalcount'], $page, $perpage, $currenturl);

echo $OUTPUT->footer();
