<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External service definitions for block_grade_activity.
 *
 * @package    block_grade_activity
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'block_grade_activity_enable_grading' => [
        'classname'   => 'block_grade_activity_external',
        'methodname'  => 'enable_grading',
        'classpath'   => 'blocks/grade_activity/externallib.php',
        'description' => 'Create a manual grade item for the given activity.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/grade:edit',
    ],

    'block_grade_activity_save_grades' => [
        'classname'   => 'block_grade_activity_external',
        'methodname'  => 'save_grades',
        'classpath'   => 'blocks/grade_activity/externallib.php',
        'description' => 'Save student grades for the linked grade item.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/grade:edit',
    ],
];
