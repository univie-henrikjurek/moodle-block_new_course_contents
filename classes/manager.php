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

    /** @var int Cache lifetime in seconds (10 minutes) */
    const CACHE_LIFETIME = 600;

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
     * @param string $sort Sort order: 'title', 'shortname', or 'lastaccessed'
     * @param string $search Search term for filtering courses
     * @param string $grouping Grouping: 'all', 'inprogress', 'future', 'past', 'favourites'
     * @return array Array of course data with activity counts
     */
    public static function get_courses_with_activities($userid, $sort = 'lastaccessed', $search = '', $grouping = 'all') {
        global $DB, $PAGE;
        
        $cachekey = "{$userid}_{$sort}_{$search}_{$grouping}";
        $cached = self::get_cache()->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }
        
        $customlastaccess = $DB->get_records('block_newcoursecontents_lastseen', ['userid' => $userid], '', 'courseid, lastseen');
        $lastseens = [];
        foreach ($customlastaccess as $record) {
            $lastseens[$record->courseid] = $record->lastseen;
        }
        
        $courses = \enrol_get_my_courses(['id', 'fullname', 'shortname', 'summary', 'timecreated', 'visible', 'idnumber', 'category'], null, 0, false);
        
        if (empty($courses)) {
            $courses = \get_user_courses($userid);
        }
        
        if (empty($courses)) {
            return [];
        }

        if (!empty($search)) {
            $searchlower = strtolower($search);
            $courses = array_filter($courses, function($course) use ($searchlower) {
                return stripos($course->fullname, $searchlower) !== false 
                    || stripos($course->shortname, $searchlower) !== false;
            });
        }

        if (empty($courses)) {
            return [];
        }

        $courses = self::apply_grouping($courses, $grouping, $userid);

        if (empty($courses)) {
            return [];
        }

        $courseimgs = [];
        $output = $PAGE->get_renderer('core');
        
        foreach ($courses as $course) {
            $file = \course_get_courseimage($course);
            if ($file) {
                $url = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                );
                $courseimgs[$course->id] = $url->out(false);
            } else {
                $coursecontext = \core\context\course::instance($course->id);
                $generatedurl = $output->get_generated_url_for_course($coursecontext, $course->id);
                $courseimgs[$course->id] = $generatedurl ?: null;
            }
        }
        
        $result = [];
        
        foreach ($courses as $course) {
            $lastseen = $lastseens[$course->id] ?? null;
            
            $activitycount = self::count_unseen_activities($course->id, $userid);
            $lastactivity = self::get_last_activity_time($course->id);
            
            $result[] = [
                'id' => $course->id,
                'fullname' => \format_string($course->fullname),
                'shortname' => \format_string($course->shortname),
                'shortname_raw' => $course->shortname,
                'visible' => $course->visible,
                'courseimage' => $courseimgs[$course->id],
                'viewurl' => new \moodle_url('/course/view.php', ['id' => $course->id]),
                'activitycount' => $activitycount,
                'lastactivity' => $lastactivity,
                'lastseen' => $lastseen,
                'lastvisitstr' => $lastseen ? self::format_time($lastseen) : null,
                'lasttimestr' => $lastactivity ? self::format_time($lastactivity) : null,
                'hasnewactivity' => $activitycount > 0,
                'detailurl' => new \moodle_url('/blocks/newcoursecontents/detail.php', [
                    'courseid' => $course->id
                ]),
                'courseurl' => new \moodle_url('/course/view.php', [
                    'id' => $course->id
                ]),
                'badgeclass' => self::get_badge_class($activitycount),
            ];
        }

        usort($result, function($a, $b) use ($sort) {
            if ($sort === 'title') {
                return strcasecmp($a['fullname'], $b['fullname']);
            } else if ($sort === 'shortname') {
                return strcasecmp($a['shortname_raw'], $b['shortname_raw']);
            } else {
                return $b['activitycount'] - $a['activitycount'];
            }
        });

        self::get_cache()->set($cachekey, $result);

        return $result;
    }

    /**
     * Apply grouping filter to courses.
     *
     * @param array $courses Array of course objects
     * @param string $grouping Grouping type: 'all', 'inprogress', 'future', 'past', 'favourites'
     * @param int $userid User ID
     * @return array Filtered courses
     */
    protected static function apply_grouping($courses, $grouping, $userid) {
        global $DB;
        
        $now = time();
        
        switch ($grouping) {
            case 'inprogress':
                return array_filter($courses, function($course) {
                    $hasstart = isset($course->startdate) && $course->startdate > 0;
                    $hasend = isset($course->enddate) && $course->enddate > 0;
                    
                    if ($hasstart && $course->startdate > $now) {
                        return false;
                    }
                    if ($hasend && $course->enddate < $now) {
                        return false;
                    }
                    return true;
                });
                
            case 'future':
                return array_filter($courses, function($course) {
                    return isset($course->startdate) && $course->startdate > $now;
                });
                
            case 'past':
                return array_filter($courses, function($course) {
                    if (!isset($course->enddate) || $course->enddate == 0) {
                        return isset($course->startdate) && $course->startdate > 0 && ($now - $course->startdate) > 31536000;
                    }
                    return $course->enddate < $now;
                });
                
            case 'favourites':
                $favs = self::get_user_favourite_courses($userid);
                return array_filter($courses, function($course) use ($favs) {
                    return isset($favs[$course->id]);
                });
                
            case 'all':
            default:
                return $courses;
        }
    }

    /**
     * Get user's favourite courses.
     *
     * @param int $userid User ID
     * @return array Array of favourite course IDs
     */
    protected static function get_user_favourite_courses($userid) {
        global $DB;
        
        $favs = $DB->get_records('user_favourites', [
            'userid' => $userid,
            'component' => 'core_course',
            'itemtype' => 'courses'
        ], '', 'itemid');
        
        $result = [];
        foreach ($favs as $fav) {
            $result[$fav->itemid] = true;
        }
        
        return $result;
    }

    /**
     * Get activity details for a specific course.
     * Returns only activities that have been added since last course access
     * and are not yet marked as seen by the user.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return array Array of activity data
     */
    public static function get_course_details($courseid, $userid) {
        global $DB;

        $lastseen = self::get_lastseen($userid, $courseid);
        $seencms = self::get_seen_cms($userid);
        
        // First get all course modules with basic info
        $sql = "SELECT cm.id as cmid, cm.module, cm.instance, cm.added, cm.section,
                       m.name as modname, cm.visible
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = ? AND cm.visible = 1
                ORDER BY cm.section, cm.added DESC";

        $records = $DB->get_records_sql($sql, [$courseid]);

        // Now get names and timemodified for all instances
        // Group by module type for efficient querying
        $instancenames = [];
        $instancemodified = [];
        
        // Get all module types that have name field
        $moduletypes = $DB->get_records('modules', null, 'name');
        
        foreach ($moduletypes as $mod) {
            $tablename = $mod->name;
            // Check if table exists and has name field
            if (!$DB->get_manager()->table_exists($tablename)) {
                continue;
            }
            
            // Try to get name and timemodified from module table
            try {
                $instances = $DB->get_records_sql("
                    SELECT id, name, timemodified 
                    FROM {" . $tablename . "} 
                    WHERE course = ?
                ", [$courseid]);
                
                foreach ($instances as $instance) {
                    $instancenames[$mod->id][$instance->id] = $instance->name ?? 'Unnamed';
                    $instancemodified[$mod->id][$instance->id] = $instance->timemodified ?? null;
                }
            } catch (\Exception $e) {
                // Table doesn't have expected structure, skip
                continue;
            }
        }

        $activities = [];
        $now = time();

        foreach ($records as $record) {
            $instancename = $instancenames[$record->module][$record->instance] ?? 'Unnamed';
            $instancemod = $instancemodified[$record->module][$record->instance] ?? null;
            
            // Determine the activity time: use timemodified if available and newer
            if ($instancemod !== null && $instancemod > $record->added) {
                $acttime = $instancemod;
            } else {
                $acttime = $record->added;
            }

            // Skip if older than last visit
            if ($lastseen !== null && $acttime <= $lastseen) {
                continue;
            }

            // Skip if already seen
            if (isset($seencms[$record->cmid])) {
                continue;
            }

            // Skip if too old (more than 60 days)
            if ($lastseen !== null) {
                $dayssince = floor(($now - $acttime) / 86400);
                if ($dayssince > 60) {
                    continue;
                }
            }

            $acttype = self::get_activity_type_label($record->modname);
            $iconname = self::get_module_icon_name($record->modname);
            
            // Check if activity was modified after being added
            $modifiedafteradd = ($instancemod !== null && $instancemod > $record->added);

            $activities[] = [
                'cmid' => $record->cmid,
                'type' => $acttype,
                'typename' => $record->modname,
                'iconname' => $iconname,
                'name' => \format_string($instancename),
                'timeadded' => $record->added,
                'timeaddedstr' => self::format_time($record->added),
                'timemodified' => $instancemod,
                'timemodifiedstr' => ($instancemod !== null && $instancemod != $record->added) ? self::format_time($instancemod) : null,
                'time' => $acttime,
                'timestr' => self::format_time($acttime),
                'url' => new \moodle_url('/mod/' . $record->modname . '/view.php', [
                    'id' => $record->cmid
                ]),
                'section' => $record->section,
                'isnew' => ($lastseen === null || $acttime > $lastseen),
                'wasmodified' => $modifiedafteradd,
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

        self::get_cache()->purge();

        return true;
    }

    /**
     * Get the last seen timestamp for a user in a course.
     * Uses Moodle's user_lastaccess table.
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
        ], 'lastseen');

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
     * Count activities in a course that are newer than user's last access
     * and not yet marked as seen.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return int Count of unseen activities
     */
    protected static function count_unseen_activities($courseid, $userid) {
        global $DB;

        $lastseenrecord = $DB->get_record('block_newcoursecontents_lastseen', [
            'userid' => $userid,
            'courseid' => $courseid
        ], 'lastseen');
        
        $lastseen = $lastseenrecord ? $lastseenrecord->lastseen : null;
        $seencms = self::get_seen_cms($userid);

        $params = [$courseid];
        
        if ($lastseen === null) {
            $sql = "SELECT COUNT(cm.id)
                    FROM {course_modules} cm
                    WHERE cm.course = ? AND cm.visible = 1";
        } else {
            $sql = "SELECT COUNT(cm.id)
                    FROM {course_modules} cm
                    WHERE cm.course = ? AND cm.visible = 1
                    AND cm.added > ?";
            $params[] = $lastseen;
        }

        if (!empty($seencms)) {
            $notincmids = array_keys($seencms);
            list($insql, $inparams) = $DB->get_in_or_equal($notincmids, \SQL_PARAMS_QM, 'param', false);
            $sql .= " AND cm.id " . $insql;
            $params = array_merge($params, $inparams);
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get all seen course modules for a user.
     *
     * @param int $userid User ID
     * @return array Array of cmid => timeseen
     */
    protected static function get_seen_cms($userid) {
        global $DB;

        $records = $DB->get_records('block_newcoursecontents_seen', ['userid' => $userid], '', 'cmid, timeseen');
        
        $result = [];
        foreach ($records as $record) {
            $result[$record->cmid] = $record->timeseen;
        }
        
        return $result;
    }

    /**
     * Mark a specific activity as seen by a user.
     *
     * @param int $userid User ID
     * @param int $cmid Course module ID
     * @return bool Success
     */
    public static function mark_activity_seen($userid, $cmid) {
        global $DB;

        $now = time();
        
        $existing = $DB->get_record('block_newcoursecontents_seen', [
            'userid' => $userid,
            'cmid' => $cmid
        ]);

        if ($existing) {
            return true;
        }

        $DB->insert_record('block_newcoursecontents_seen', [
            'userid' => $userid,
            'cmid' => $cmid,
            'timeseen' => $now
        ]);

        return true;
    }

    /**
     * Mark multiple activities as seen by a user.
     *
     * @param int $userid User ID
     * @param array $cmids Array of course module IDs
     * @return bool Success
     */
    public static function mark_activities_seen($userid, $cmids) {
        global $DB;

        if (empty($cmids)) {
            return true;
        }

        $now = time();
        $existing = $DB->get_records('block_newcoursecontents_seen', [
            'userid' => $userid
        ], '', 'cmid');

        $existingcmids = array_column($existing, 'cmid');

        foreach ($cmids as $cmid) {
            if (!in_array($cmid, $existingcmids)) {
                $DB->insert_record('block_newcoursecontents_seen', [
                    'userid' => $userid,
                    'cmid' => $cmid,
                    'timeseen' => $now
                ]);
            }
        }

        return true;
    }

    /**
     * Get the last activity time in a course.
     *
     * @param int $courseid Course ID
     * @return int|null Unix timestamp
     */
    protected static function get_last_activity_time($courseid) {
        global $DB;

        $sql = "SELECT MAX(cm.added) as maxtime
                FROM {course_modules} cm
                WHERE cm.course = ? AND cm.visible = 1";
        $params = [$courseid];

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
     * Get module icon name for use with Moodle's pix helper.
     *
     * @param string $modname Module name (e.g., 'forum', 'assign')
     * @return string Icon name for pix helper (e.g., 'i/forum')
     */
    public static function get_module_icon_name($modname) {
        return 'i/' . $modname;
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
        self::get_cache()->purge();
    }

    /**
     * Clear all caches.
     */
    public static function clear_all_caches() {
        self::get_cache()->purge();
    }
}
