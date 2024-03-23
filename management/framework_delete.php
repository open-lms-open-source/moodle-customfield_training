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
 * Delete training framework.
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

$id = required_param('id', PARAM_INT);

require_login();

$framework = $DB->get_record('customfield_training_frameworks', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($framework->contextid);
require_capability('customfield/training:manageframeworks', $context);

$pageurl = new moodle_url('/customfield/field/training/management/framework_delete.php', ['id' => $framework->id]);
management::setup_framework_page($pageurl, $context, $framework);

$returnurl = new moodle_url('/customfield/field/training/management/index.php', ['contextid' => $context->id]);

if (!framework::is_deletable($framework)) {
    // We should not get here.
    redirect($returnurl, get_string('error'));
}

$data = clone($framework);

$form = new \customfield_training\local\form\framework_delete(null, ['data' => $data]);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    framework::delete($data->id);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('framework_delete', 'customfield_training'));
echo $form->render();
echo $OUTPUT->footer();
