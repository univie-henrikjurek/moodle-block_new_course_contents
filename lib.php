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
 * Library functions for block_newcoursecontents.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve the files from the block_newcoursecontents settings pages.
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context $context Context object
 * @param string $filearea File area
 * @param array $args Extra arguments
 * @param bool $forcedownload Whether to force download
 * @param array $options Additional options
 * @return bool|null False if file not found, sends file if found
 */
function block_newcoursecontents_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    if ($filearea !== 'icon') {
        return false;
    }

    require_login($course, true);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'block_newcoursecontents', $filearea, 0);

    if (empty($files)) {
        return false;
    }

    $file = reset($files);

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Add nodes to the navigation drawer.
 *
 * @param navigation_node $navigation Navigation node
 * @param stdClass $course Course object
 * @param context_course $context Context object
 */
function block_newcoursecontents_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:view', $context)) {
        $url = new moodle_url('/blocks/newcoursecontents/detail.php', ['courseid' => $course->id]);
        $node = navigation_node::create(
            get_string('pluginname', 'block_newcoursecontents'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'block_newcoursecontents',
            new pix_icon('i/course', '')
        );
        
        if (isset($navigation->children)) {
            $navigation->add_node($node, 'resource');
        }
    }
}

/**
 * Cron function for cleanup tasks.
 */
function block_newcoursecontents_cron() {
    global $DB;

    $retentiondays = 90;
    $cuttimestamp = time() - ($retentiondays * DAYSECS);

    $DB->delete_records_select(
        'block_newcoursecontents_lastseen',
        'lastseen < ?',
        [$cuttimestamp]
    );

    mtrace('Cleaned up old entries from block_newcoursecontents_lastseen older than ' . $retentiondays . ' days.');
}
