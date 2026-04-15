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
 * JavaScript for block_newcoursecontents filter functionality.
 *
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {set_user_preference} from 'core/user';
import Templates from 'core/templates';
import Notification from 'core/notification';

let searchTimeout = null;

const debounce = (func, wait) => {
    return function executedFunction(...args) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => func.apply(this, args), wait);
    };
};

const updateCourseList = async (blockElement) => {
    const searchInput = blockElement.querySelector('.ncc-search');
    const sortSelect = blockElement.querySelector('.ncc-sort');
    const viewRadios = blockElement.querySelectorAll('.ncc-view');
    const courseList = blockElement.querySelector('.ncc-course-list');

    const search = searchInput ? searchInput.value : '';
    const sort = sortSelect ? sortSelect.value : 'lastaccessed';
    let view = 'card';
    viewRadios.forEach(radio => {
        if (radio.checked) {
            view = radio.value;
        }
    });

    courseList.innerHTML = '<div class="text-center p-3"><i class="fa-solid fa-spinner fa-spin"></i></div>';

    try {
        const results = await fetchMany([{
            methodname: 'block_newcoursecontents_get_courses',
            args: {
                sort: sort,
                search: search,
                view: view
            }
        }]);

        const courses = results[0];
        const badgecolor = blockElement.querySelector('.badge')?.style.backgroundColor || '#0063A6';

        courses.forEach(course => {
            course.badgecolor = badgecolor;
            if (course.lastactivity) {
                course.lasttimestr = course.lasttimestr || '';
            }
            if (course.lastseen) {
                course.lastvisitstr = course.lastvisitstr || '';
            }
        });

        const context = {courses: courses};
        const {html} = await Templates.renderForPromise('block_newcoursecontents/course_list', context);
        courseList.innerHTML = html;

    } catch (error) {
        courseList.innerHTML = '<div class="alert alert-danger">Error loading courses</div>';
        Notification.exception(error);
    }
};

const debouncedUpdate = debounce(updateCourseList, 300);

export const init = () => {
    document.addEventListener('change', async (e) => {
        const block = e.target.closest('.block_newcoursecontents');
        if (!block) {
            return;
        }

        if (e.target.classList.contains('ncc-search')) {
            const search = e.target.value;
            await set_user_preference('block_newcoursecontents_user_search_preference', search);
            debouncedUpdate(block);
        }

        if (e.target.classList.contains('ncc-sort')) {
            const sort = e.target.value;
            await set_user_preference('block_newcoursecontents_user_sort_preference', sort);
            updateCourseList(block);
        }

        if (e.target.classList.contains('ncc-view')) {
            const view = e.target.value;
            await set_user_preference('block_newcoursecontents_user_view_preference', view);
            const courseList = block.querySelector('.ncc-course-list');
            if (view === 'list') {
                courseList.classList.add('ncc-view-list');
            } else {
                courseList.classList.remove('ncc-view-list');
            }
        }
    });

    document.addEventListener('keyup', async (e) => {
        if (!e.target.classList.contains('ncc-search')) {
            return;
        }

        const block = e.target.closest('.block_newcoursecontents');
        if (!block) {
            return;
        }

        const search = e.target.value;
        await set_user_preference('block_newcoursecontents_user_search_preference', search);
        debouncedUpdate(block);
    });
};
