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
 * External API for block_grade_activity.
 *
 * Provides AJAX endpoints for enabling grading and saving grades.
 *
 * @package    block_grade_activity
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/gradelib.php');

class block_grade_activity_external extends external_api {

    // -----------------------------------------------------------------------
    // enable_grading
    // -----------------------------------------------------------------------

    /**
     * Parameter definition for enable_grading.
     *
     * @return external_function_parameters
     */
    public static function enable_grading_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Enable grading for an activity by creating a manual grade item.
     *
     * @param  int   $cmid Course module ID.
     * @return array ['success' => bool, 'itemid' => int]
     */
    public static function enable_grading($cmid) {
        global $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::enable_grading_parameters(), ['cmid' => $cmid]);
        $cmid   = $params['cmid'];

        // Security: validate context and capability.
        $cm      = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('moodle/grade:edit', $context);

        // Create grade item via lib.php helper.
        require_once($CFG->dirroot . '/blocks/grade_activity/lib.php');
        $gradeitem = block_grade_activity_create_grade_item($cmid);

        if (!$gradeitem) {
            throw new moodle_exception('alreadyenabled', 'block_grade_activity');
        }

        return [
            'success' => true,
            'itemid'  => (int) $gradeitem->id,
        ];
    }

    /**
     * Return definition for enable_grading.
     *
     * @return external_single_structure
     */
    public static function enable_grading_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
            'itemid'  => new external_value(PARAM_INT, 'The new grade item ID'),
        ]);
    }

    // -----------------------------------------------------------------------
    // save_grades
    // -----------------------------------------------------------------------

    /**
     * Parameter definition for save_grades.
     *
     * @return external_function_parameters
     */
    public static function save_grades_parameters() {
        return new external_function_parameters([
            'cmid'   => new external_value(PARAM_INT, 'Course module ID'),
            'grades' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'grade'  => new external_value(PARAM_FLOAT, 'Grade value'),
                ]),
                'Array of user grades'
            ),
        ]);
    }

    /**
     * Save grades to the gradebook.
     *
     * @param  int   $cmid   Course module ID.
     * @param  array $grades Array of ['userid' => int, 'grade' => float].
     * @return array ['success' => bool, 'updated' => int]
     */
    public static function save_grades($cmid, $grades) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::save_grades_parameters(), [
            'cmid'   => $cmid,
            'grades' => $grades,
        ]);

        $cmid   = $params['cmid'];
        $grades = $params['grades'];

        // Security: validate context and capability.
        $cm      = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('moodle/grade:edit', $context);

        // Fetch the linked grade item.
        $link = $DB->get_record('block_grade_activity_link', ['cmid' => $cmid], '*', MUST_EXIST);
        $gradeitem = grade_item::fetch(['id' => $link->itemid]);

        if (!$gradeitem) {
            throw new moodle_exception('gradeitemnotfound', 'block_grade_activity');
        }

        $grademax = (float) $gradeitem->grademax;
        $grademin = (float) $gradeitem->grademin;
        $updated  = 0;

        foreach ($grades as $entry) {
            $userid = (int) $entry['userid'];
            $grade  = (float) $entry['grade'];

            // Validate range.
            if ($grade < $grademin || $grade > $grademax) {
                throw new invalid_parameter_exception(
                    get_string('gradeoutofrange', 'block_grade_activity', [
                        'userid'   => $userid,
                        'grade'    => $grade,
                        'grademin' => $grademin,
                        'grademax' => $grademax,
                    ])
                );
            }

            // Ensure the user is enrolled in the course.
            $coursecontext = context_course::instance($cm->course);
            if (!is_enrolled($coursecontext, $userid)) {
                throw new invalid_parameter_exception(
                    get_string('usernotenrolled', 'block_grade_activity', $userid)
                );
            }

            $gradeitem->update_final_grade($userid, $grade, 'block_grade_activity');
            $updated++;
        }

        return [
            'success' => true,
            'updated' => $updated,
        ];
    }

    /**
     * Return definition for save_grades.
     *
     * @return external_single_structure
     */
    public static function save_grades_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
            'updated' => new external_value(PARAM_INT, 'Number of grades updated'),
        ]);
    }
}
