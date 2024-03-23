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
 * Add a new training framework.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use customfield_training\local\framework;
use customfield_training\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

if (!empty($_SERVER['HTTP_X_LEGACY_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}

require('../../../../config.php');
require_once("$CFG->libdir/filelib.php");

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('customfield/training:manageframeworks', $context);

$pageurl = new moodle_url('/customfield/field/training/management/framework_create.php', ['contextid' => $context->id]);
management::setup_index_page($pageurl, $context, $context->id);

$returnurl = new moodle_url('/customfield/field/training/management/index.php', ['contextid' => $context->id]);

$framework = new \stdClass();
$framework->contextid = $context->id;
$framework->name = '';
$framework->idnumber = '';
$framework->description = '';
$framework->descriptionformat = FORMAT_HTML;
$framework->restrictedcompletion = 0;
$framework->public = 0;

$editoroptions = framework::get_description_editor_options();

$form = new \customfield_training\local\form\framework_create(null, ['data' => $framework, 'editoroptions' => $editoroptions]);
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    framework::create((array)$data);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('framework_create', 'customfield_training'));
echo $form->render();
echo $OUTPUT->footer();
