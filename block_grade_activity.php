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
 * Block definition for block_grade_activity.
 *
 * @package    block_grade_activity
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

class block_grade_activity extends block_base {

    /**
     * Initialise the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_grade_activity');
    }

    /**
     * Restrict block to activity (module) pages only.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'mod'    => true,
            'site'   => false,
            'course' => false,
            'my'     => false,
        ];
    }

    /**
     * Only one instance per activity page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Control whether a user can add this block to a given page.
     * Implements exclusion logic: returns false if the activity already has
     * a native grade item in the gradebook.
     *
     * @param  \moodle_page $page
     * @return bool
     */
    public function user_can_addto($page) {
        if (!parent::user_can_addto($page)) {
            return false;
        }

        // Must be in a module context.
        if ($page->context->contextlevel !== CONTEXT_MODULE) {
            return false;
        }

        // Require grading or management capability.
        $canedit   = has_capability('moodle/grade:edit', $page->context);
        $canmanage = has_capability('moodle/course:manageactivities', $page->context);
        if (!$canedit && !$canmanage) {
            return false;
        }

        // Exclusion: if the activity already has a native grade item, deny adding.
        $cm = get_coursemodule_from_id('', $page->context->instanceid, 0, false, IGNORE_MISSING);
        if ($cm) {
            $items = grade_get_grades($cm->course, 'mod', $cm->modname, $cm->instance);
            if (!empty($items->items)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $DB, $OUTPUT, $PAGE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        $context = $this->context;

        // Must be in a module context.
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return $this->content;
        }

        // Check capabilities.
        $canedit   = has_capability('moodle/grade:edit', $context);
        $canmanage = has_capability('moodle/course:manageactivities', $context);
        if (!$canedit && !$canmanage) {
            return $this->content;
        }

        $cm     = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
        $course = get_course($cm->course);

        // Check if a custom grade item link exists.
        $link = $DB->get_record('block_grade_activity_link', ['cmid' => $cm->id]);

        if (!$link) {
            // ---- Setup state: show "Enable Grading" button ----
            $activityname = format_string($cm->name);
            $data = [
                'cmid'       => $cm->id,
                'buttontext' => get_string('enablegrading', 'block_grade_activity', $activityname),
            ];

            $PAGE->requires->js_call_amd('block_grade_activity/grade_handler', 'initSetup', [$cm->id]);
            $this->content->text = $OUTPUT->render_from_template('block_grade_activity/setup', $data);
        } else {
            // ---- Active state: show grading interface ----
            require_once($CFG->libdir . '/gradelib.php');
            $gradeitem = grade_item::fetch(['id' => $link->itemid]);

            if (!$gradeitem) {
                // Orphaned link – clean up.
                $DB->delete_records('block_grade_activity_link', ['id' => $link->id]);
                return $this->content;
            }

            // Fetch enrolled students respecting group mode.
            $students = $this->get_students($cm, $course);

            // Fetch existing grades keyed by userid.
            $existinggrades = $DB->get_records('grade_grades', ['itemid' => $link->itemid], '', 'userid, finalgrade');

            $grademax    = (float) $gradeitem->grademax;
            $grademaxfmt = format_float($grademax, 0);

            $studentdata = [];
            foreach ($students as $student) {
                $existinggrade = '';
                if (isset($existinggrades[$student->id]) && !is_null($existinggrades[$student->id]->finalgrade)) {
                    $existinggrade = format_float($existinggrades[$student->id]->finalgrade, 2);
                }

                $studentdata[] = [
                    'userid'      => $student->id,
                    'userpicture' => $OUTPUT->user_picture($student, ['size' => 35]),
                    'fullname'    => fullname($student),
                    'grade'       => $existinggrade,
                    'grademax'    => $grademaxfmt,
                ];
            }

            $data = [
                'cmid'        => $cm->id,
                'grademax'    => $grademaxfmt,
                'students'    => $studentdata,
                'hasstudents' => !empty($studentdata),
            ];

            $PAGE->requires->js_call_amd(
                'block_grade_activity/grade_handler',
                'init',
                [$cm->id, $grademax]
            );

            $this->content->text = $OUTPUT->render_from_template('block_grade_activity/grading_interface', $data);
        }

        return $this->content;
    }

    /**
     * Fetch enrolled students, respecting the activity's group mode.
     *
     * @param  stdClass $cm     Course module record.
     * @param  stdClass $course Course record.
     * @return array    Array of user objects.
     */
    private function get_students($cm, $course) {
        global $USER;

        $coursecontext = context_course::instance($course->id);
        $groupmode     = groups_get_activity_groupmode($cm);

        if ($groupmode == SEPARATEGROUPS) {
            // Only show students belonging to the teacher's group(s).
            $groups   = groups_get_all_groups($course->id, $USER->id, $cm->groupingid);
            $groupids = array_keys($groups);

            if (empty($groupids)) {
                return [];
            }

            $students = [];
            foreach ($groupids as $groupid) {
                $members = groups_get_members($groupid, 'u.*');
                foreach ($members as $member) {
                    if (has_capability('moodle/course:view', $coursecontext, $member->id)) {
                        $students[$member->id] = $member;
                    }
                }
            }
            return array_values($students);
        }

        // No groups or Visible Groups – return all enrolled users who can view the course.
        return get_enrolled_users($coursecontext, 'moodle/course:view');
    }
}
