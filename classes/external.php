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
 * External functions for block_newcoursecontents.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_newcoursecontents;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class external extends \external_api {

    /**
     * Returns description of get_course_details parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_course_details_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Returns description of get_course_details returns.
     *
     * @return \external_description
     */
    public static function get_course_details_returns() {
        return new \external_single_structure([
            'coursename' => new \external_value(PARAM_TEXT, 'Course name'),
            'courseurl' => new \external_value(PARAM_URL, 'Course URL'),
            'lastvisitstr' => new \external_value(PARAM_TEXT, 'Last visit string'),
            'activities' => new \external_multiple_structure(
                new \external_single_structure([
                    'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
                    'type' => new \external_value(PARAM_TEXT, 'Activity type'),
                    'typename' => new \external_value(PARAM_TEXT, 'Module type name'),
                    'iconname' => new \external_value(PARAM_TEXT, 'Icon name for pix helper'),
                    'name' => new \external_value(PARAM_TEXT, 'Activity name'),
                    'timeaddedstr' => new \external_value(PARAM_TEXT, 'Time added string'),
                    'timemodifiedstr' => new \external_value(PARAM_TEXT, 'Time modified string', VALUE_OPTIONAL),
                    'url' => new \external_value(PARAM_URL, 'Activity URL'),
                    'section' => new \external_value(PARAM_INT, 'Section number'),
                    'isnew' => new \external_value(PARAM_BOOL, 'Is new'),
                    'wasmodified' => new \external_value(PARAM_BOOL, 'Was modified'),
                ])
            ),
            'activitycount' => new \external_value(PARAM_INT, 'Total activity count'),
            'str_close' => new \external_value(PARAM_TEXT, 'Close button text'),
            'str_lastvisit' => new \external_value(PARAM_TEXT, 'Last visit label'),
            'str_new' => new \external_value(PARAM_TEXT, 'New badge text'),
            'str_modified' => new \external_value(PARAM_TEXT, 'Modified badge text'),
            'str_added' => new \external_value(PARAM_TEXT, 'Added label'),
            'str_open' => new \external_value(PARAM_TEXT, 'Open button text'),
            'str_nochanges' => new \external_value(PARAM_TEXT, 'No changes message'),
            'str_gotocourse' => new \external_value(PARAM_TEXT, 'Go to course button text'),
        ]);
    }

    /**
     * Get course details for AJAX modal display.
     *
     * @param int $courseid Course ID
     * @return array Course details with activities
     * @throws \moodle_exception
     */
    public static function get_course_details($courseid) {
        global $USER, $DB;

        $params = self::validate_parameters(self::get_course_details_parameters(), [
            'courseid' => $courseid,
        ]);

        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname');
        if (!$course) {
            throw new \moodle_exception('invalidcourse', 'error', '', null, 'Course not found');
        }

        require_login($course, false);

        $context = \context_course::instance($courseid);
        require_capability('moodle/course:view', $context);

        $activities = manager::get_course_details($courseid, $USER->id);
        $lastseen = manager::get_lastseen($USER->id, $courseid);

        $activitydata = [];
        foreach ($activities as $activity) {
            $activitydata[] = [
                'cmid' => $activity['cmid'],
                'type' => $activity['type'],
                'typename' => $activity['typename'],
                'iconname' => $activity['iconname'],
                'name' => $activity['name'],
                'timeaddedstr' => $activity['timeaddedstr'],
                'timemodifiedstr' => $activity['timemodifiedstr'],
                'url' => $activity['url']->out(false),
                'section' => $activity['section'],
                'isnew' => $activity['isnew'],
                'wasmodified' => $activity['wasmodified'],
            ];
        }

        manager::mark_activities_seen($USER->id, array_column($activities, 'cmid'));
        manager::update_lastseen($USER->id, $courseid);

        return [
            'coursename' => format_string($course->fullname),
            'courseurl' => (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            'lastvisitstr' => $lastseen ? manager::format_time($lastseen) : get_string('never', 'moodle'),
            'activities' => $activitydata,
            'activitycount' => count($activitydata),
            'str_close' => get_string('close', 'moodle'),
            'str_lastvisit' => get_string('lastvisit', 'block_newcoursecontents'),
            'str_new' => get_string('new', 'block_newcoursecontents'),
            'str_modified' => get_string('modified', 'block_newcoursecontents'),
            'str_added' => get_string('added', 'block_newcoursecontents'),
            'str_open' => get_string('open', 'block_newcoursecontents'),
            'str_nochanges' => get_string('nochanges', 'block_newcoursecontents'),
            'str_gotocourse' => get_string('gotocourse', 'block_newcoursecontents'),
            'str_markasseen' => get_string('markasseen', 'block_newcoursecontents'),
        ];
    }

    /**
     * Returns description of mark_activities_seen parameters.
     *
     * @return \external_function_parameters
     */
    public static function mark_activities_seen_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Returns description of mark_activities_seen returns.
     *
     * @return \external_description
     */
    public static function mark_activities_seen_returns() {
        return new \external_value(PARAM_BOOL, 'Success');
    }

    /**
     * Mark all activities in a course as seen.
     *
     * @param int $courseid Course ID
     * @return bool Success
     */
    public static function mark_activities_seen($courseid) {
        global $USER, $DB;

        $params = self::validate_parameters(self::mark_activities_seen_parameters(), [
            'courseid' => $courseid,
        ]);

        $course = $DB->get_record('course', ['id' => $courseid], 'id');
        if (!$course) {
            throw new \moodle_exception('invalidcourse', 'error');
        }

        require_login($course, false);

        $context = \context_course::instance($courseid);
        require_capability('moodle/course:view', $context);

        $activities = manager::get_course_details($courseid, $USER->id);
        $cmids = array_column($activities, 'cmid');
        
        if (!empty($cmids)) {
            manager::mark_activities_seen($USER->id, $cmids);
        }

        return true;
    }

    /**
     * Returns description of mark_activity_seen parameters.
     *
     * @return \external_function_parameters
     */
    public static function mark_activity_seen_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Returns description of mark_activity_seen returns.
     *
     * @return \external_description
     */
    public static function mark_activity_seen_returns() {
        return new \external_value(PARAM_BOOL, 'Success');
    }

    /**
     * Mark a single activity as seen.
     *
     * @param int $cmid Course module ID
     * @return bool Success
     */
    public static function mark_activity_seen($cmid) {
        global $USER, $DB;

        $params = self::validate_parameters(self::mark_activity_seen_parameters(), [
            'cmid' => $cmid,
        ]);

        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id, course');
        if (!$cm) {
            throw new \moodle_exception('invalidcoursemodule', 'error');
        }

        require_login($cm->course, false);

        $context = \context_course::instance($cm->course);
        require_capability('moodle/course:view', $context);

        manager::mark_activity_seen($USER->id, $cmid);
        manager::clear_cache($USER->id);

        return true;
    }

    /**
     * Returns description of get_courses parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_courses_parameters() {
        return new \external_function_parameters([
            'sort' => new \external_value(PARAM_ALPHA, 'Sort order', VALUE_DEFAULT, 'lastaccessed'),
            'search' => new \external_value(PARAM_TEXT, 'Search term', VALUE_DEFAULT, ''),
            'view' => new \external_value(PARAM_ALPHA, 'View mode', VALUE_DEFAULT, 'card'),
            'grouping' => new \external_value(PARAM_ALPHA, 'Grouping filter', VALUE_DEFAULT, 'all'),
        ]);
    }

    /**
     * Returns description of get_courses returns.
     *
     * @return \external_description
     */
    public static function get_courses_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'Course ID'),
                'fullname' => new \external_value(PARAM_TEXT, 'Course full name'),
                'shortname' => new \external_value(PARAM_TEXT, 'Course short name'),
                'visible' => new \external_value(PARAM_INT, 'Course visibility'),
                'courseimage' => new \external_value(PARAM_URL, 'Course image URL', VALUE_OPTIONAL),
                'activitycount' => new \external_value(PARAM_INT, 'Activity count'),
                'lastactivity' => new \external_value(PARAM_INT, 'Last activity timestamp', VALUE_OPTIONAL),
                'lastseen' => new \external_value(PARAM_INT, 'Last seen timestamp', VALUE_OPTIONAL),
                'detailurl' => new \external_value(PARAM_URL, 'Detail page URL'),
                'courseurl' => new \external_value(PARAM_URL, 'Course URL'),
                'badgeclass' => new \external_value(PARAM_TEXT, 'Badge CSS class'),
                'lasttimestr' => new \external_value(PARAM_TEXT, 'Last activity string', VALUE_OPTIONAL),
                'lastvisitstr' => new \external_value(PARAM_TEXT, 'Last visit string', VALUE_OPTIONAL),
                'hasnewactivity' => new \external_value(PARAM_BOOL, 'Has new activity'),
                'badgecolor' => new \external_value(PARAM_TEXT, 'Badge color'),
            ])
        );
    }

    /**
     * Get courses for the block with filters.
     *
     * @param string $sort Sort order
     * @param string $search Search term
     * @param string $view View mode
     * @param string $grouping Grouping filter
     * @return array Courses data
     */
    public static function get_courses($sort = 'lastaccessed', $search = '', $view = 'card', $grouping = 'all') {
        global $USER;

        $params = self::validate_parameters(self::get_courses_parameters(), [
            'sort' => $sort,
            'search' => $search,
            'view' => $view,
            'grouping' => $grouping,
        ]);

        require_login();

        $courses = manager::get_courses_with_activities($USER->id, $params['sort'], $params['search'], $params['grouping']);

        $badgecolor = get_config('block_newcoursecontents', 'badgecolor');
        if (empty($badgecolor)) {
            $badgecolor = '#0063A6';
        }

        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => $course['id'],
                'fullname' => $course['fullname'],
                'shortname' => $course['shortname'],
                'visible' => $course['visible'],
                'courseimage' => $course['courseimage'] ?? null,
                'activitycount' => $course['activitycount'],
                'lastactivity' => $course['lastactivity'] ?? null,
                'lastseen' => $course['lastseen'] ?? null,
                'detailurl' => is_object($course['detailurl']) ? $course['detailurl']->out(false) : $course['detailurl'],
                'courseurl' => is_object($course['courseurl']) ? $course['courseurl']->out(false) : $course['courseurl'],
                'badgeclass' => $course['badgeclass'],
                'lasttimestr' => $course['lasttimestr'] ?? null,
                'lastvisitstr' => $course['lastvisitstr'] ?? null,
                'hasnewactivity' => $course['hasnewactivity'],
                'badgecolor' => $badgecolor,
            ];
        }

        return $result;
    }
}
