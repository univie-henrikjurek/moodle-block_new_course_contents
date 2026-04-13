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
 * Manager class for block_newcoursecontents.
 *
 * Handles data retrieval, caching, and business logic.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_newcoursecontents;

defined('MOODLE_INTERNAL') || die();

class manager {

    /** @var int Cache lifetime in seconds (30 minutes) */
    const CACHE_LIFETIME = 1800;

    /** @var string Cache prefix */
    const CACHE_PREFIX = 'block_ncc_';

    /** @var \cache_store|null */
    protected static $cache = null;

    /**
     * Get the cache instance.
     *
     * @return \cache_store
     */
    protected static function get_cache() {
        if (self::$cache === null) {
            self::$cache = \cache::make_from_params(
                \cache_store::MODE_APPLICATION,
                'block_newcoursecontents',
                'courses',
                [],
                ['simplekeys' => true, 'ttl' => self::CACHE_LIFETIME]
            );
        }
        return self::$cache;
    }

    /**
     * Get all courses with new activities for a user.
     *
     * @param int $userid User ID
     * @return array Array of course data with activity counts
     */
    public static function get_courses_with_activities($userid) {
        global $DB;
        
        $cache = self::get_cache();
        $cachekey = self::CACHE_PREFIX . 'courses_' . $userid;
        
        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }
        
        $moodlelastaccess = $DB->get_records('user_lastaccess', ['userid' => $userid], '', 'courseid, timeaccess');
        $lastseens = array_column($moodlelastaccess, 'timeaccess', 'courseid');
        
        $courses = \enrol_get_my_courses(['id', 'fullname', 'shortname', 'summary', 'timecreated', 'visible', 'idnumber', 'category'], null, 0, false);
        
        if (empty($courses)) {
            $courses = \get_user_courses($userid);
        }
        
        if (empty($courses)) {
            return [];
        }

        $result = [];
        
        foreach ($courses as $course) {
            $lastseen = $lastseens[$course->id] ?? null;
            
            $activitycount = self::count_course_activities($course->id, $lastseen);
            $lastactivity = self::get_last_activity_time($course->id, $lastseen);
            
            $result[] = [
                'id' => $course->id,
                'fullname' => \format_string($course->fullname),
                'shortname' => \format_string($course->shortname),
                'activitycount' => $activitycount,
                'lastactivity' => $lastactivity,
                'lastseen' => $lastseen,
                'detailurl' => new \moodle_url('/blocks/newcoursecontents/detail.php', [
                    'courseid' => $course->id
                ]),
                'courseurl' => new \moodle_url('/course/view.php', [
                    'id' => $course->id
                ]),
                'badgeclass' => self::get_badge_class($activitycount),
            ];
        }

        usort($result, function($a, $b) {
            return $b['activitycount'] - $a['activitycount'];
        });

        $cache->set($cachekey, $result);

        return $result;
    }

    /**
     * Get activity details for a specific course.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return array Array of activity data
     */
    public static function get_course_details($courseid, $userid) {
        global $DB;

        $lastseen = self::get_lastseen($userid, $courseid);
        
        $sql = "SELECT cm.id as cmid, cm.module, cm.instance, cm.added, cm.modified,
                       m.name as modname, cm.visible,
                       cm.sectionnum,
                       r.id as resourceid, r.name as resourcename,
                       r.timemodified as resourcemodified
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                LEFT JOIN (
                    SELECT id, name, course, timemodified, 1 as istopic FROM {forum} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 2 as istopic FROM {assign} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 3 as istopic FROM {quiz} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 4 as istopic FROM {resource} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 5 as istopic FROM {lesson} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 6 as istopic FROM {workshop} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 7 as istopic FROM {choice} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 8 as istopic FROM {page} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 9 as istopic FROM {url} WHERE course = ?
                    UNION ALL
                    SELECT id, name, course, timemodified, 10 as istopic FROM {h5pactivity} WHERE course = ?
                ) r ON r.id = cm.instance AND r.istopic = m.id
                WHERE cm.course = ? AND cm.visible = 1
                ORDER BY cm.sectionnum, cm.added";

        $params = array_fill(0, 10, $courseid);
        $params[] = $courseid;

        $records = $DB->get_records_sql($sql, $params);

        $activities = [];
        $now = time();

        foreach ($records as $record) {
            if ($record->resourcemodified === null) {
                $acttime = $record->added;
            } else {
                $acttime = $record->resourcemodified;
            }

            if ($lastseen !== null && $acttime <= $lastseen) {
                continue;
            }

            if ($lastseen !== null) {
                $dayssince = floor(($now - $acttime) / 86400);
                if ($dayssince > 60) {
                    continue;
                }
            }

            $acttype = self::get_activity_type_label($record->modname);
            $icon = self::get_module_icon($record->modname);

            $activities[] = [
                'cmid' => $record->cmid,
                'type' => $acttype,
                'typename' => $record->modname,
                'name' => \format_string($record->resourcename ?? 'Unnamed'),
                'time' => $acttime,
                'timestr' => self::format_time($acttime),
                'typeicon' => $icon,
                'url' => new \moodle_url('/mod/' . $record->modname . '/view.php', [
                    'id' => $record->cmid
                ]),
                'section' => $record->sectionnum,
                'isnew' => ($lastseen === null || $acttime > $lastseen),
            ];
        }

        return $activities;
    }

    /**
     * Update the last seen timestamp for a user in a course.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return bool Success
     */
    public static function update_lastseen($userid, $courseid) {
        global $DB;

        $now = time();
        
        $existing = $DB->get_record('block_newcoursecontents_lastseen', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        if ($existing) {
            $DB->update_record('block_newcoursecontents_lastseen', [
                'id' => $existing->id,
                'lastseen' => $now
            ]);
        } else {
            $DB->insert_record('block_newcoursecontents_lastseen', [
                'userid' => $userid,
                'courseid' => $courseid,
                'lastseen' => $now,
                'timecreated' => $now
            ]);
        }

        self::get_cache()->delete('block_ncc_courses_' . $userid);

        return true;
    }

    /**
     * Get the last seen timestamp for a user in a course.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return int|null Unix timestamp or null
     */
    public static function get_lastseen($userid, $courseid) {
        global $DB;

        $record = $DB->get_record('block_newcoursecontents_lastseen', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        return $record ? $record->lastseen : null;
    }

    /**
     * Get all lastseen records for a user.
     *
     * @param int $userid User ID
     * @return array Array of courseid => lastseen
     */
    protected static function get_lastseens_by_user($userid) {
        global $DB;

        $records = $DB->get_records('block_newcoursecontents_lastseen', [
            'userid' => $userid
        ], '', 'courseid, lastseen');

        $result = [];
        foreach ($records as $record) {
            $result[$record->courseid] = $record->lastseen;
        }

        return $result;
    }

    /**
     * Count new/changed activities in a course since last seen.
     *
     * @param int $courseid Course ID
     * @param int|null $lastseen Last seen timestamp
     * @return int Count of activities
     */
    protected static function count_course_activities($courseid, $lastseen) {
        global $DB;

        if ($lastseen === null) {
            $sql = "SELECT COUNT(cm.id)
                    FROM {course_modules} cm
                    WHERE cm.course = ? AND cm.visible = 1";
            $params = [$courseid];
        } else {
            $sql = "SELECT COUNT(cm.id)
                    FROM {course_modules} cm
                    WHERE cm.course = ? AND cm.visible = 1
                    AND (cm.added > ? OR cm.modified > ?)";
            $params = [$courseid, $lastseen, $lastseen];
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get the last activity time in a course.
     *
     * @param int $courseid Course ID
     * @param int|null $lastseen Last seen timestamp
     * @return int|null Unix timestamp
     */
    protected static function get_last_activity_time($courseid, $lastseen) {
        global $DB;

        if ($lastseen === null) {
            $sql = "SELECT MAX(cm.added) as maxtime
                    FROM {course_modules} cm
                    WHERE cm.course = ? AND cm.visible = 1";
            $params = [$courseid];
        } else {
            $sql = "SELECT MAX(MAX(cm.added, cm.modified)) as maxtime
                    FROM {course_modules} cm
                    WHERE cm.course = ? AND cm.visible = 1
                    AND (cm.added > ? OR cm.modified > ?)";
            $params = [$courseid, $lastseen, $lastseen];
        }

        $result = $DB->get_record_sql($sql, $params);

        return $result && $result->maxtime ? $result->maxtime : null;
    }

    /**
     * Get badge CSS class based on activity count.
     *
     * @param int $count Activity count
     * @return string CSS class name
     */
    public static function get_badge_class($count) {
        if ($count == 0) {
            return 'badge-secondary';
        } else if ($count <= 3) {
            return 'badge-warning';
        } else if ($count <= 10) {
            return 'badge-orange';
        } else {
            return 'badge-danger';
        }
    }

    /**
     * Get human-readable activity type label.
     *
     * @param string $modname Module name
     * @return string Translated label
     */
    protected static function get_activity_type_label($modname) {
        $modulenames = \get_module_types_names();
        return $modulenames[$modname] ?? ucfirst($modname);
    }

    /**
     * Get module icon HTML.
     *
     * @param string $modname Module name
     * @return string HTML img tag
     */
    protected static function get_module_icon($modname) {
        $iconurl = \moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'mod_' . $modname,
            'icon',
            null,
            null,
            null
        );

        return \html_writer::empty_tag('img', [
            'src' => $iconurl,
            'class' => 'icon activityicon',
            'alt' => '',
            'title' => $modname
        ]);
    }

    /**
     * Format timestamp to human-readable string.
     *
     * @param int $timestamp Unix timestamp
     * @return string Formatted string
     */
    public static function format_time($timestamp) {
        if (!$timestamp) {
            return '-';
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return \get_string('justnow', 'block_newcoursecontents');
        } else if ($diff < 3600) {
            $mins = floor($diff / 60);
            return \get_string('minutesago', 'block_newcoursecontents', $mins);
        } else if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return \get_string('hoursago', 'block_newcoursecontents', $hours);
        } else if ($diff < 604800) {
            $days = floor($diff / 86400);
            return \get_string('daysago', 'block_newcoursecontents', $days);
        } else {
            return \userdate($timestamp, \get_string('strftimedaydate', 'langconfig'));
        }
    }

    /**
     * Clear cache for a user.
     *
     * @param int $userid User ID
     */
    public static function clear_cache($userid) {
        self::get_cache()->delete('block_ncc_courses_' . $userid);
    }

    /**
     * Clear all caches.
     */
    public static function clear_all_caches() {
        self::get_cache()->purge();
    }
}
