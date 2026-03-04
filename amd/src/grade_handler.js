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
 * @copyright  CentricApp LTD
 * @author     Dev Team <dev@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/notification',
    'core/toast',
    'core/str'
], function(Ajax, Notification, Toast, Str) {

    /**
     * Run a callback when the DOM is ready (so block content is available).
     *
     * @param {Function} fn Callback to run.
     */
    var whenReady = function(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            setTimeout(fn, 0);
        }
    };

    // ---------------------------------------------------------------------------
    // Setup state – "Enable Grading" button
    // ---------------------------------------------------------------------------

    /**
     * Initialise the setup-state UI.
     *
     * @param {number} cmid Course-module ID.
     */
    var initSetup = function(cmid) {
        whenReady(function() {
            var container = document.querySelector('.block_grade_activity_setup');
            if (!container) {
                return;
            }

            var enableBtn = container.querySelector('#grade-activity-enable');
            if (!enableBtn) {
                return;
            }

            var originalButtonText = enableBtn.textContent;

            enableBtn.addEventListener('click', function() {
                enableBtn.disabled = true;
                enableBtn.textContent = '…';

                Ajax.call([{
                    methodname: 'block_grade_activity_enable_grading',
                    args: {cmid: cmid},
                    done: function() {
                        window.location.reload();
                    },
                    fail: function(err) {
                        enableBtn.disabled = false;
                        enableBtn.textContent = originalButtonText;
                        Notification.exception(err);
                    }
                }]);
            });
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
    var init = function(cmid, grademax) {
        whenReady(function() {
            var container = document.querySelector('.block_grade_activity_grading');
            if (!container) {
                return;
            }

            var searchInput = container.querySelector('#grade-activity-search');
            var saveButton = container.querySelector('#grade-activity-save');
            var gradeInputs = container.querySelectorAll('.grade-input');

            if (!saveButton || gradeInputs.length === 0) {
                return;
            }

            var originalValues = new Map();
            gradeInputs.forEach(function(input) {
                originalValues.set(input.dataset.userid, input.value);
            });

            // ----- Search / filter -------------------------------------------------

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var query = searchInput.value.trim().toLowerCase();
                    var rows = container.querySelectorAll('#grade-activity-table tbody tr');

                    rows.forEach(function(row) {
                        var nameCell = row.querySelector('.student-name');
                        if (!nameCell) {
                            return;
                        }
                        var name = nameCell.textContent.toLowerCase();
                        row.hidden = query !== '' && !name.includes(query);
                    });
                });
            }

            // ----- Dirty-state tracking --------------------------------------------

            var checkDirty = function() {
                var dirty = false;
                gradeInputs.forEach(function(input) {
                    if (input.value !== originalValues.get(input.dataset.userid)) {
                        dirty = true;
                    }
                });
                saveButton.disabled = !dirty;
            };

            gradeInputs.forEach(function(input) {
                input.addEventListener('input', checkDirty);
            });

            // ----- Validation helper -----------------------------------------------

            var isValidGrade = function(val) {
                if (val === '') {
                    return true;
                }
                var num = parseFloat(val);
                return !isNaN(num) && num >= 0 && num <= grademax;
            };

            gradeInputs.forEach(function(input) {
                input.addEventListener('blur', function() {
                    if (!isValidGrade(input.value)) {
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            });

            // ----- Save handler ----------------------------------------------------

            saveButton.addEventListener('click', function() {
                var grades = [];
                var hasInvalid = false;

                gradeInputs.forEach(function(input) {
                    var uid = input.dataset.userid;

                    if (input.value === originalValues.get(uid)) {
                        return;
                    }

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
                        grade: parseFloat(input.value)
                    });
                });

                if (hasInvalid) {
                    Str.get_string('invalidgrade', 'block_grade_activity', grademax).then(function(msg) {
                        Notification.addNotification({message: msg, type: 'error'});
                    });
                    return;
                }

                if (grades.length === 0) {
                    return;
                }

                saveButton.disabled = true;
                var originalLabel = saveButton.textContent;

                Str.get_string('saving', 'block_grade_activity').then(function(savingText) {
                    saveButton.textContent = savingText;

                    Ajax.call([{
                        methodname: 'block_grade_activity_save_grades',
                        args: {cmid: cmid, grades: grades},
                        done: function() {
                            gradeInputs.forEach(function(input) {
                                originalValues.set(input.dataset.userid, input.value);
                            });

                            saveButton.textContent = originalLabel;
                            saveButton.disabled = true;

                            Str.get_string('gradessaved', 'block_grade_activity').then(function(successMsg) {
                                Toast.add(successMsg);
                            });
                        },
                        fail: function(err) {
                            saveButton.textContent = originalLabel;
                            checkDirty();
                            Notification.exception(err);
                        }
                    }]);
                });
            });
        });
    };

    return {
        initSetup: initSetup,
        init: init
    };
});
