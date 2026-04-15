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
import Notification from 'core/notification';
import {setUserPreference} from 'core_user/repository';
import * as View from 'block_newcoursecontents/view';
import SELECTORS from 'block_newcoursecontents/selectors';

const updatePreferences = (filter, value) => {
    let type = null;
    if (filter === 'display') {
        type = 'block_newcoursecontents_user_view_preference';
    } else if (filter === 'sort') {
        type = 'block_newcoursecontents_user_sort_preference';
    } else {
        type = 'block_newcoursecontents_user_grouping_preference';
    }

    return setUserPreference(type, value)
        .catch(Notification.exception);
};

const registerSelector = root => {
    const Selector = root.find(SELECTORS.FILTERS);

    CustomEvents.define(Selector, [CustomEvents.events.activate]);

    Selector.on(
        CustomEvents.events.activate,
        SELECTORS.FILTER_OPTION,
        (e, data) => {
            const option = $(e.target);

            if (option.hasClass('active')) {
                return;
            }

            const filter = option.attr('data-filter');
            const pref = option.attr('data-pref');

            root.find(SELECTORS.courseView.region).attr('data-' + filter, option.attr('data-value'));
            updatePreferences(filter, pref);

            const page = document.querySelector(SELECTORS.region.selectBlock);
            const input = page.querySelector(SELECTORS.region.searchInput);
            if (input.value !== '') {
                input.value = '';
                View.clearSearch(root);
            } else {
                View.init(root);
            }

            data.originalEvent.preventDefault();
        }
    );

    Selector.on(
        CustomEvents.events.activate,
        SELECTORS.DISPLAY_OPTION,
        (e, data) => {
            const option = $(e.target);

            if (option.hasClass('active')) {
                return;
            }

            const filter = option.attr('data-display-option');
            const pref = option.attr('data-pref');

            root.find(SELECTORS.courseView.region).attr('data-display', option.attr('data-value'));
            updatePreferences(filter, pref);
            View.reset(root);
            data.originalEvent.preventDefault();
        }
    );
};

export const init = root => {
    root = $(root);
    registerSelector(root);
};
