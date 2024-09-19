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
 * Customfield training plugin
 *
 * @package   customfield_training
 * @copyright 2024 Open LMS
 * @author    Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://www.openlms.net/
 */

$plugin->version   = 2024091900;
$plugin->requires  = 2024091700.00; // 4.5.0
$plugin->component = 'customfield_training';
$plugin->supported = [405, 405];

$plugin->dependencies = [
    'local_openlms' => 2024091900,
];
