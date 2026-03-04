# Grade Activity block

A Moodle block that adds manual grading from any activity page and syncs grades to the course gradebook.

**Copyright:** CentricApp LTD  
**Dev Team:** dev@centricapp.co.il

---

## Description

The **Grade Activity** block appears only on activity (module) pages. It lets teachers:

1. **Enable grading** for an activity that does not have its own grade item (e.g. a Page or Label). This creates a manual grade item in the course gradebook linked to that activity.
2. **Grade enrolled students** in a simple table: enter grades and save. Grades are written to the linked grade item.
3. **Search/filter** the student list and see at a glance when there are unsaved changes.

The block is hidden from the “Add a block” list when the activity already has a built-in grade item (e.g. Assignment, Quiz), so it is intended for activities that otherwise would not appear in the gradebook.

## Requirements

- Moodle 4.0 or later (requires 2022041900).

## Installation

1. Copy the `grade_activity` folder into `blocks/` in your Moodle installation.
2. Visit **Site administration → Notifications** and complete the upgrade.
3. The block will be available when editing a course; add it from the block chooser on an activity page (where applicable).

## Usage

1. Open a course and go to an activity that does not have its own grade item (e.g. a Page, URL, or Book).
2. Turn editing on and add the **Grade Activity** block to the activity page.
3. In the block, click **Enable Grading for [Activity name]** to create the linked grade item in the gradebook.
4. Use the grading table to enter grades for enrolled students. Use the search box to filter by name if needed.
5. Click **Save Grades** to push grades to the gradebook. A success message is shown when saving completes.

## Capabilities

- **Add a Grade Activity block** (`block_grade_activity:addinstance`) – allows adding the block on an activity page (subject to the block’s own rules, e.g. no duplicate block, activity without existing grade item).
- **Grade students** – uses standard Moodle grading capabilities (`moodle/grade:edit` or `moodle/course:manageactivities` in the activity context).

## Technical notes

- The block uses **external (AJAX) services** for “enable grading” and “save grades”. Ensure web services and the block’s external functions are not disabled.
- Grades are stored in the standard Moodle gradebook (manual grade items). The link between the block instance and the grade item is stored in the plugin’s own table.
- The block only allows one instance per activity page and only appears in the block list when the activity does not already have a grade item from its module.

## License

GNU GPL v3 or later. See the license block in the plugin source files.

## Support

Dev Team: dev@centricapp.co.il
