<?php
// This file is part of the tool_certificate plugin for Moodle - https://moodle.org/
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
 * training frameworks.
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
/** @var stdClass $USER */

require_once('../../../../config.php');
require_once("$CFG->libdir/adminlib.php");

$id = required_param('id', PARAM_INT);

require_login();

$framework = $DB->get_record('customfield_training_frameworks', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($framework->contextid);
require_capability('customfield/training:viewframeworks', $context);

$pageurl = new \moodle_url('/customfield/field/training/framework.php', ['id' => $framework->id]);

management::setup_framework_page($pageurl, $context, $framework);

/** @var \local_openlms\output\dialog_form\renderer $dialogformoutput */
$dialogformoutput = $PAGE->get_renderer('local_openlms', 'dialog_form');

echo $OUTPUT->header();

$buttons = [];
if (has_capability('customfield/training:manageframeworks', $context)) {
    $url = new \moodle_url('/customfield/field/training/management/framework_update.php', ['id' => $framework->id]);
    $button = new \local_openlms\output\dialog_form\button($url, get_string('framework_update', 'customfield_training'));
    $buttons[] = $dialogformoutput->render($button);

    if (\customfield_training\local\framework::is_deletable($framework)) {
        $url = new \moodle_url('/customfield/field/training/management/framework_delete.php', ['id' => $framework->id]);
        $button = new \local_openlms\output\dialog_form\button($url, get_string('framework_delete', 'customfield_training'));
        $button->set_after_submit($button::AFTER_SUBMIT_REDIRECT);
        $buttons[] = $dialogformoutput->render($button);
    }
}

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons float-end');
}

if ($framework->description) {
    $description = format_text($framework->description, $framework->descriptionformat, ['context' => $context]);
    echo $OUTPUT->box($description);
}

echo '<dl class="row">';
if ($framework->idnumber === null) {
    $idnumber = get_string('notset', 'customfield_training');
} else {
    $idnumber = s($framework->idnumber);
}
echo '<dt class="col-3">' . get_string('idnumber') . ':</dt><dd class="col-9">' . $idnumber . '</dd>';
echo '<dt class="col-3">' . get_string('public', 'customfield_training') . ':</dt><dd class="col-9">'
    . ($framework->public ? get_string('yes') : get_string('no')) . '</dd>';
echo '<dt class="col-3">' . get_string('context', 'role') . ':</dt><dd class="col-9">' . $context->get_context_name(false) . '</dd>';
echo '<dt class="col-3">' . get_string('requiredtraining', 'customfield_training') . ':</dt><dd class="col-9">' . number_format($framework->requiredtraining, 0, '', ' ') . '</dd>';
echo '<dt class="col-3">' . get_string('restrictedcompletion', 'customfield_training') . ':</dt><dd class="col-9">'
    . ($framework->restrictedcompletion ? get_string('yes') : get_string('no')) . '</dd>';
echo '<dt class="col-3">' . get_string('archived', 'customfield_training') . ':</dt><dd class="col-9">'
    . ($framework->archived ? get_string('yes') : get_string('no')) . '</dd>';
echo '</dl>';

echo $OUTPUT->heading(get_string('fields', 'customfield_training'), 4);

$table = new \customfield_training\table\fields($pageurl, $framework);
$table->out($table->pagesize, false);

if (!$framework->archived && has_capability('customfield/training:manageframeworks', $context)) {
    $url = new \moodle_url('/customfield/field/training/management/field_add.php', ['frameworkid' => $framework->id]);
    $button = get_string('field_add', 'customfield_training');
    $button = new \local_openlms\output\dialog_form\button($url, $button);
    $addbutton = $dialogformoutput->render($button);
    echo '<br /><div class="buttons">' . $addbutton . '</div>';
}

echo $OUTPUT->footer();
