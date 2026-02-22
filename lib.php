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
 * Library functions for block_grade_activity.
 *
 * @package    block_grade_activity
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

/**
 * Create a manual grade item linked to a course module.
 *
 * The grade item is named "<Activity Name> G.I" and linked via
 * the block_grade_activity_link table.
 *
 * @param  int             $cmid Course module ID.
 * @return grade_item|bool The created grade_item or false if already exists.
 * @throws moodle_exception
 */
function block_grade_activity_create_grade_item($cmid) {
    global $DB;

    $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

    // Prevent duplicates.
    if ($DB->record_exists('block_grade_activity_link', ['cmid' => $cmid])) {
        return false;
    }

    // Create the manual grade item.
    $gradeitem = new grade_item([
        'courseid' => $cm->course,
        'itemtype' => 'manual',
        'itemname' => format_string($cm->name) . ' G.I',
        'grademax' => 100.00,
        'grademin' => 0.00,
    ], false);

    $gradeitem->insert();

    // Store the link between the grade item and the course module.
    $link = new stdClass();
    $link->itemid      = $gradeitem->id;
    $link->cmid        = $cmid;
    $link->timecreated = time();
    $DB->insert_record('block_grade_activity_link', $link);

    return $gradeitem;
}
