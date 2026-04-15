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
 * JavaScript for block_newcoursecontents.
 *
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Templates from 'core/templates';

/**
 * Register event listeners for the block.
 */
export const init = () => {
    document.addEventListener('click', (e) => {
        const badgeLink = e.target.closest('.activity-badge a');
        if (badgeLink) {
            e.preventDefault();
            const href = badgeLink.getAttribute('href');
            window.location.href = href;
        }
    });
};

/**
 * Fetch course details via AJAX (for potential future modal use).
 *
 * @param {number} courseid The course ID
 * @param {number} userid The user ID
 * @returns {Promise} The AJAX call promise
 */
export const fetchCourseDetails = (courseid, userid) => {
    return fetchMany([{
        methodname: 'block_newcoursecontents_get_details',
        args: {
            courseid: courseid,
            userid: userid
        }
    }])[0];
};

/**
 * Render a detail modal with activity data.
 *
 * @param {Object} context Template context
 * @returns {Promise} The rendered HTML
 */
export const renderDetailModal = (context) => {
    return Templates.render('block_newcoursecontents/detail_page', context);
};
