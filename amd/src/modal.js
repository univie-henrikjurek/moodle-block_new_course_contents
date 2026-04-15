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
 * JavaScript for modal handling in block_newcoursecontents.
 *
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';
import Templates from 'core/templates';
import Notification from 'core/notification';

let modalContainer = null;
let backdrop = null;

const removeModal = () => {
    if (modalContainer) {
        modalContainer.remove();
        modalContainer = null;
    }
    if (backdrop) {
        backdrop.remove();
        backdrop = null;
    }
    document.body.style.overflow = '';
};

const handleCourseClick = async (e) => {
    const trigger = e.target.closest('[data-ncc-courseid]');
    if (!trigger) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const courseid = parseInt(trigger.dataset.nccCourseid, 10);

    trigger.disabled = true;

    try {
        const results = await fetchMany([{
            methodname: 'block_newcoursecontents_get_course_details',
            args: {
                courseid: courseid
            }
        }]);

        const data = results[0];
        await showModal(data, courseid);

    } catch (error) {
        Notification.exception(error);
    } finally {
        trigger.disabled = false;
    }
};

const handleMarkSeenClick = async (e) => {
    const button = e.target.closest('.ncc-mark-seen');
    if (!button) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const cmid = parseInt(button.dataset.cmid, 10);
    const activityItem = button.closest('.ncc-activity-item');

    if (!activityItem || !cmid) {
        return;
    }

    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>...';

    try {
        await fetchMany([{
            methodname: 'block_newcoursecontents_mark_activity_seen',
            args: {
                cmid: cmid
            }
        }]);

        activityItem.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        activityItem.style.opacity = '0';
        activityItem.style.transform = 'translateX(20px)';

        setTimeout(() => {
            activityItem.remove();

            const remainingItems = document.querySelectorAll('.ncc-activity-item');
            const modalBody = document.querySelector('.ncc-modal-body');
            if (remainingItems.length === 0 && modalBody) {
                const msg = getString('nochanges', 'block_newcoursecontents');
                modalBody.innerHTML = `<div class="alert alert-success">
                    <i class="fa-regular fa-check-circle me-2"></i>${msg}
                </div>`;
            }
        }, 300);

    } catch (error) {
        button.disabled = false;
        button.innerHTML = '<i class="fa-regular fa-check me-1"></i>' + getString('markasseen', 'block_newcoursecontents');
        Notification.exception(error);
    }
};

const showModal = async (data, courseid) => {
    removeModal();

    const context = {
        courseid: courseid,
        coursename: data.coursename,
        courseurl: data.courseurl,
        lastvisitstr: data.lastvisitstr,
        activities: data.activities,
        activitycount: data.activitycount,
        hasactivities: data.activitycount > 0,
        str_close: data.str_close,
        str_lastvisit: data.str_lastvisit,
        str_new: data.str_new,
        str_modified: data.str_modified,
        str_added: data.str_added,
        str_open: data.str_open,
        str_nochanges: data.str_nochanges,
        str_gotocourse: data.str_gotocourse,
        str_markasseen: data.str_markasseen,
    };

    const {html} = await Templates.renderForPromise('block_newcoursecontents/modal_content', context);

    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
    `;
    document.body.appendChild(backdrop);

    modalContainer = document.createElement('div');
    modalContainer.className = 'modal fade show d-block';
    modalContainer.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1050;
        overflow-x: hidden;
        overflow-y: auto;
    `;
    modalContainer.innerHTML = html;
    document.body.appendChild(modalContainer);
    document.body.style.overflow = 'hidden';

    const modalDialog = modalContainer.querySelector('.modal-dialog');
    if (modalDialog) {
        modalDialog.style.cssText = `
            position: relative;
            margin: 1.75rem auto;
            max-width: 800px;
            animation: modalIn 0.3s ease-out;
        `;
    }

    const modalContent = modalContainer.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.animation = 'modalIn 0.3s ease-out';
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);

    modalContainer.addEventListener('click', (e) => {
        if (e.target === modalContainer) {
            removeModal();
        }
    });

    modalContainer.addEventListener('click', (e) => {
        if (e.target.closest('.ncc-modal-close')) {
            e.preventDefault();
            removeModal();
        }
    });

    modalContainer.addEventListener('click', handleMarkSeenClick);
};

export const init = () => {
    document.addEventListener('click', handleCourseClick);
};
