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
 * Detail page for block_newcoursecontents.
 *
 * Shows all new/changed activities in a specific course.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course, false);

$PAGE->set_context($context);
$PAGE->set_url(new \moodle_url('/blocks/newcoursecontents/detail.php', ['courseid' => $courseid]));
$PAGE->set_title(\format_string($course->fullname));
$PAGE->set_heading(\format_string($course->fullname));

require_once(__DIR__ . '/classes/manager.php');
require_once(__DIR__ . '/classes/output/renderer.php');

$activities = \block_newcoursecontents\manager::get_course_details($courseid, $USER->id);

$cmids = array_column($activities, 'cmid');
if (!empty($cmids)) {
    \block_newcoursecontents\manager::mark_activities_seen($USER->id, $cmids);
}

foreach ($activities as &$activity) {
    $activity['url'] = $activity['url']->out(false);
}

$lastseen = \block_newcoursecontents\manager::get_lastseen($USER->id, $courseid);

$courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);

$templatecontext = [
    'coursename' => \format_string($course->fullname),
    'courseurl' => $courseurl->out(false),
    'lastvisitstr' => $lastseen ? \block_newcoursecontents\manager::format_time($lastseen) : \get_string('never', 'moodle'),
    'activities' => $activities,
];

$renderer = $PAGE->get_renderer('block_newcoursecontents');

echo $OUTPUT->header();
echo $renderer->render_detail_page($templatecontext);
echo $OUTPUT->footer();
