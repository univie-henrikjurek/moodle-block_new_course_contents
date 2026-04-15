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
 * JavaScript to manage view rendering for block_newcoursecontents.
 *
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as Repository from 'block_newcoursecontents/repository';
import * as CustomEvents from 'core/custom_interaction_events';
import * as Notification from 'core/notification';
import * as Templates from 'core/templates';
import {debounce} from 'core/utils';
import SELECTORS from 'block_newcoursecontents/selectors';

const TEMPLATES = {
    COURSES_CARDS: 'block_newcoursecontents/view-cards',
    COURSES_LIST: 'block_newcoursecontents/view-list',
};

let loadedCourses = [];

const getFilterValues = root => {
    const courseRegion = root.find(SELECTORS.courseView.region);
    return {
        display: courseRegion.attr('data-display'),
        grouping: courseRegion.attr('data-grouping'),
        sort: courseRegion.attr('data-sort'),
    };
};

const renderCourses = (root, courses) => {
    const filters = getFilterValues(root);
    let templateName = filters.display === 'list' ? TEMPLATES.COURSES_LIST : TEMPLATES.COURSES_CARDS;

    if (!courses || courses.length === 0) {
        return Templates.render('block_newcoursecontents/no-courses', {
            nocoursesimg: '',
            message: ''
        });
    }

    const context = {
        courses: courses.map(course => ({
            ...course,
            viewurl: course.courseurl,
            badgecolor: course.badgecolor || '#0063A6'
        }))
    };

    return Templates.render(templateName, context);
};

const fetchCourses = (filters, inputValue = '') => {
    const args = {
        grouping: filters.grouping || 'all',
        sort: filters.sort || 'lastaccessed',
    };

    if (inputValue) {
        args.search = inputValue;
    }

    return Repository.getCourses(args);
};

const init = (root) => {
    root = $(root);
    loadedCourses = [];

    if (!root.attr('data-init')) {
        const page = document.querySelector(SELECTORS.region.selectBlock);
        registerEventListeners(root, page);
        root.attr('data-init', true);
    }

    const filters = getFilterValues(root);
    const page = document.querySelector(SELECTORS.region.selectBlock);
    const input = page.querySelector(SELECTORS.region.searchInput);

    if (input && input.value) {
        loadCourses(root, filters, input.value);
    } else {
        loadCourses(root, filters);
    }
};

const loadCourses = (root, filters, searchValue = '') => {
    const contentRegion = root.find(SELECTORS.courseView.regionContent);

    contentRegion.addClass('loading');

    fetchCourses(filters, searchValue).then(courses => {
        loadedCourses = courses;
        return renderCourses(root, courses);
    }).then((html, js) => {
        contentRegion.removeClass('loading');
        return Templates.replaceNodeContents(contentRegion, html, js);
    }).catch(error => {
        contentRegion.removeClass('loading');
        Notification.exception(error);
    });
};

const registerEventListeners = (root, page) => {
    CustomEvents.define(root, [CustomEvents.events.activate]);

    const input = page.querySelector(SELECTORS.region.searchInput);
    const clearIcon = page.querySelector(SELECTORS.region.clearIcon);

    if (clearIcon) {
        clearIcon.addEventListener('click', () => {
            input.value = '';
            input.focus();
            clearSearch(root);
        });
    }

    if (input) {
        input.addEventListener('input', debounce(() => {
            const filters = getFilterValues(root);
            if (input.value === '') {
                clearSearch(root);
            } else {
                loadCourses(root, filters, input.value.trim());
            }
        }, 500));
    }
};

export const clearSearch = (root) => {
    const filters = getFilterValues(root);
    loadCourses(root, filters);
};

export const reset = (root) => {
    if (!root) {
        return;
    }

    if (loadedCourses.length > 0) {
        const $root = $(root);
        renderCourses($root, loadedCourses).then((html, js) => {
            const contentRegion = $root.find(SELECTORS.courseView.regionContent);
            return Templates.replaceNodeContents(contentRegion, html, js);
        }).catch(Notification.exception);
    } else {
        init(root);
    }
};

export {init};
