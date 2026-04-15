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
 * JavaScript to manage navigation events for block_newcoursecontents.
 *
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as CustomEvents from 'core/custom_interaction_events';
import Ajax from 'core/ajax';
import * as View from 'block_newcoursecontents/view';
import SELECTORS from 'block_newcoursecontents/selectors';

const saveUserPreference = (filter, value) => {
    let prefName = null;
    if (filter === 'display') {
        prefName = 'block_newcoursecontents_user_view_preference';
    } else if (filter === 'sort') {
        prefName = 'block_newcoursecontents_user_sort_preference';
    } else if (filter === 'grouping') {
        prefName = 'block_newcoursecontents_user_grouping_preference';
    } else {
        return Promise.resolve();
    }

    return Ajax.call([{
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: [{
                name: prefName,
                value: value
            }]
        }
    }])[0];
};

const registerFilterEvents = (root) => {
    const filterRegion = root.find(SELECTORS.FILTERS);
    if (filterRegion.length === 0) {
        return;
    }

    CustomEvents.define(filterRegion, [CustomEvents.events.activate]);

    filterRegion.on(
        CustomEvents.events.activate,
        SELECTORS.FILTER_OPTION,
        (e, data) => {
            const option = $(e.target);
            const filterType = option.attr('data-filter');
            const value = option.attr('data-value');

            if (option.hasClass('active')) {
                return;
            }

            const courseRegion = root.find(SELECTORS.courseView.region);
            if (courseRegion.length > 0) {
                courseRegion.attr('data-' + filterType, value);
            }

            if (filterType === 'display') {
                root.find('[name="display"][value="' + value + '"]').prop('checked', true);
            }

            saveUserPreference(filterType, value);

            const page = document.querySelector(SELECTORS.region.selectBlock);
            if (page) {
                const input = page.querySelector(SELECTORS.region.searchInput);
                if (input && input.value !== '') {
                    input.value = '';
                    View.clearSearch(root);
                } else {
                    View.init(root);
                }
            } else {
                View.init(root);
            }

            data.originalEvent.preventDefault();
        }
    );
};

const registerDisplayToggle = (root) => {
    const displayBtns = root.find('[name="display"]');

    displayBtns.on('change', (e) => {
        const value = $(e.target).val();
        const courseRegion = root.find(SELECTORS.courseView.region);

        if (courseRegion.length > 0) {
            courseRegion.attr('data-display', value);
        }

        saveUserPreference('display', value);
        View.reset(root);
    });
};

export const init = root => {
    root = $(root);
    registerFilterEvents(root);
    registerDisplayToggle(root);
};
