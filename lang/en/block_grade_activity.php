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
 * Language strings for block_grade_activity.
 *
 * @package    block_grade_activity
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname']          = 'Grade Activity';
$string['grade_activity:addinstance']   = 'Add a Grade Activity block';
$string['grade_activity:myaddinstance'] = 'Add a Grade Activity block to the My page';

// Setup state.
$string['enablegrading'] = 'Enable Grading for {$a}';

// Grading interface.
$string['searchstudents'] = 'Search students…';
$string['userpicture']    = 'Picture';
$string['fullname']       = 'Full Name';
$string['grade']          = 'Grade';
$string['savegrades']     = 'Save Grades';
$string['nostudents']     = 'No enrolled students found.';

// AJAX / notifications.
$string['gradessaved']       = 'Grades saved successfully.';
$string['alreadyenabled']    = 'Grading is already enabled for this activity.';
$string['gradeitemnotfound'] = 'The linked grade item could not be found.';
$string['gradeoutofrange']   = 'Grade {$a->grade} for user {$a->userid} is out of the allowed range ({$a->grademin}–{$a->grademax}).';
$string['usernotenrolled']   = 'User {$a} is not enrolled in this course.';
$string['saving']            = 'Saving…';
$string['invalidgrade']      = 'Please enter a valid number between 0 and {$a}.';
