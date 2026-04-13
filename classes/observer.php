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
 * Event observer for block_newcoursecontents.
 *
 * Tracks when users view course modules.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_newcoursecontents;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Mark activity as seen when user views a course module.
     *
     * @param \core\event\course_module_viewed $event
     */
    public static function course_module_viewed($event) {
        $cmid = $event->contextinstanceid;
        $userid = $event->userid;

        if (!$cmid || !$userid) {
            return;
        }

        try {
            manager::mark_activity_seen($userid, $cmid);
        } catch (\Exception $e) {
            // Silently fail - activity tracking is not critical
        }
    }
}
