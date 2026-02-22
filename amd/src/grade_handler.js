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
 * AMD module for block_grade_activity.
 *
 * Handles:
 *  - Search / filtering of the student table.
 *  - "Dirty" detection to enable / disable the Save button.
 *  - AJAX calls to enable_grading and save_grades web-service functions.
 *  - Toast notification on success.
 *
 * @module     block_grade_activity/grade_handler
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {add as addToast} from 'core/toast';
import {get_string as getString} from 'core/str';

// ---------------------------------------------------------------------------
// Setup state – "Enable Grading" button
// ---------------------------------------------------------------------------

/**
 * Initialise the setup-state UI.
 *
 * @param {number} cmid Course-module ID.
 */
export const initSetup = (cmid) => {
    const container = document.querySelector('.block_grade_activity_setup');
    if (!container) {
        return;
    }

    const enableBtn = container.querySelector('#grade-activity-enable');
    if (!enableBtn) {
        return;
    }

    enableBtn.addEventListener('click', () => {
        enableBtn.disabled = true;
        enableBtn.textContent = '…';

        Ajax.call([{
            methodname: 'block_grade_activity_enable_grading',
            args: {cmid},
            done: () => {
                // Reload the page so the block re-renders in "active" state.
                window.location.reload();
            },
            fail: (err) => {
                enableBtn.disabled = false;
                Notification.exception(err);
            },
        }]);
    });
};

// ---------------------------------------------------------------------------
// Active state – grading interface
// ---------------------------------------------------------------------------

/**
 * Initialise the grading-interface UI.
 *
 * @param {number} cmid     Course-module ID.
 * @param {number} grademax Maximum allowed grade.
 */
export const init = (cmid, grademax) => {
    const container = document.querySelector('.block_grade_activity_grading');
    if (!container) {
        return;
    }

    const searchInput = container.querySelector('#grade-activity-search');
    const saveButton  = container.querySelector('#grade-activity-save');
    const gradeInputs = container.querySelectorAll('.grade-input');

    if (!saveButton || gradeInputs.length === 0) {
        return;
    }

    // Store original values so we can detect changes.
    const originalValues = new Map();
    gradeInputs.forEach((input) => {
        originalValues.set(input.dataset.userid, input.value);
    });

    // ----- Search / filter -------------------------------------------------

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            const rows  = container.querySelectorAll('#grade-activity-table tbody tr');

            rows.forEach((row) => {
                const nameCell = row.querySelector('.student-name');
                if (!nameCell) {
                    return;
                }
                const name    = nameCell.textContent.toLowerCase();
                row.hidden = query !== '' && !name.includes(query);
            });
        });
    }

    // ----- Dirty-state tracking --------------------------------------------

    const checkDirty = () => {
        let dirty = false;
        gradeInputs.forEach((input) => {
            if (input.value !== originalValues.get(input.dataset.userid)) {
                dirty = true;
            }
        });
        saveButton.disabled = !dirty;
    };

    gradeInputs.forEach((input) => {
        input.addEventListener('input', checkDirty);
    });

    // ----- Validation helper -----------------------------------------------

    /**
     * Return true if the value is a valid grade within range.
     *
     * @param  {string}  val Raw input value.
     * @return {boolean}
     */
    const isValidGrade = (val) => {
        if (val === '') {
            return true; // Empty means "no change".
        }
        const num = parseFloat(val);
        return !isNaN(num) && num >= 0 && num <= grademax;
    };

    // Highlight invalid inputs on blur.
    gradeInputs.forEach((input) => {
        input.addEventListener('blur', () => {
            if (!isValidGrade(input.value)) {
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });
    });

    // ----- Save handler ----------------------------------------------------

    saveButton.addEventListener('click', async () => {
        // Collect changed & valid grades.
        const grades = [];
        let hasInvalid = false;

        gradeInputs.forEach((input) => {
            const uid = input.dataset.userid;

            // Skip unchanged.
            if (input.value === originalValues.get(uid)) {
                return;
            }

            // Skip empty (user cleared a grade – not sent).
            if (input.value === '') {
                return;
            }

            if (!isValidGrade(input.value)) {
                hasInvalid = true;
                input.classList.add('is-invalid');
                return;
            }

            grades.push({
                userid: parseInt(uid, 10),
                grade:  parseFloat(input.value),
            });
        });

        if (hasInvalid) {
            const msg = await getString('invalidgrade', 'block_grade_activity', grademax);
            Notification.addNotification({message: msg, type: 'error'});
            return;
        }

        if (grades.length === 0) {
            return;
        }

        // Disable button while saving.
        saveButton.disabled = true;
        const originalLabel = saveButton.textContent;
        saveButton.textContent = await getString('saving', 'block_grade_activity');

        Ajax.call([{
            methodname: 'block_grade_activity_save_grades',
            args: {cmid, grades},
            done: async () => {
                // Update stored originals.
                gradeInputs.forEach((input) => {
                    originalValues.set(input.dataset.userid, input.value);
                });

                saveButton.textContent = originalLabel;
                saveButton.disabled = true; // No longer dirty.

                const successMsg = await getString('gradessaved', 'block_grade_activity');
                addToast(successMsg);
            },
            fail: (err) => {
                saveButton.textContent = originalLabel;
                checkDirty();
                Notification.exception(err);
            },
        }]);
    });
};
