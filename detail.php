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

$PAGE->requires->strings_for_js([
    'gotodashboard',
    'markallseen',
    'markasseen',
    'nochanges',
    'gotocoursebutton',
    'lastvisit',
    'never',
    'new',
    'modified',
    'added',
    'open',
    'gotocourse',
    'close',
    'viewdetails',
], 'block_newcoursecontents');

$PAGE->requires->js_amd_inline("
    require(['jquery', 'core/ajax'], function($, Ajax) {
        $(document).on('click', '.ncc-mark-single', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var button = $(this);
            var activityItem = button.closest('.activity-item');
            var cmid = button.data('cmid');
            
            button.prop('disabled', true).html('<i class=\"fa-solid fa-spinner fa-spin me-1\"></i>...');
            
            Ajax.call([{
                methodname: 'block_newcoursecontents_mark_activity_seen',
                args: {cmid: parseInt(cmid)},
                done: function() {
                    activityItem.fadeOut(300, function() {
                        $(this).remove();
                        
                        var remaining = $('.activity-item');
                        if (remaining.length === 0) {
                            var emptyMsg = $('<div class=\"alert alert-success\">' +
                                '<i class=\"fa-regular fa-check-circle me-2\"></i>' +
                                M.util.get_string('nochanges', 'block_newcoursecontents') +
                                '</div>');
                            $('.activity-list').html(emptyMsg);
                            $('.mark-all-form').hide();
                        }
                    });
                },
                fail: function() {
                    button.prop('disabled', false).html('<i class=\"fa-regular fa-check me-1\"></i>' + 
                        M.util.get_string('markasseen', 'block_newcoursecontents'));
                }
            }]);
        });
    });
");

$markseen = optional_param('markseen', 0, PARAM_INT);
if ($markseen && $courseid) {
    \block_newcoursecontents\manager::update_lastseen($USER->id, $courseid);
    $cmids = array_column(\block_newcoursecontents\manager::get_course_details($courseid, $USER->id), 'cmid');
    if (!empty($cmids)) {
        \block_newcoursecontents\manager::mark_activities_seen($USER->id, $cmids);
    }
    redirect(new \moodle_url('/blocks/newcoursecontents/detail.php', ['courseid' => $courseid]));
}

$activities = \block_newcoursecontents\manager::get_course_details($courseid, $USER->id);

foreach ($activities as &$activity) {
    $activity['url'] = $activity['url']->out(false);
}

$lastseen = \block_newcoursecontents\manager::get_lastseen($USER->id, $courseid);

$courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
$dashboardurl = new \moodle_url('/my/');

$badgecolor = get_config('block_newcoursecontents', 'badgecolor');
if (empty($badgecolor)) {
    $badgecolor = '#0063A6';
}

$templatecontext = [
    'coursename' => \format_string($course->fullname),
    'courseurl' => $courseurl->out(false),
    'dashboardurl' => $dashboardurl->out(false),
    'courseid' => $courseid,
    'badgecolor' => $badgecolor,
    'lastvisitstr' => $lastseen ? \block_newcoursecontents\manager::format_time($lastseen) : \get_string('never', 'moodle'),
    'activities' => $activities,
    'hasactivities' => count($activities) > 0,
];

$renderer = $PAGE->get_renderer('block_newcoursecontents');

echo $OUTPUT->header();
echo $renderer->render_detail_page($templatecontext);
echo $OUTPUT->footer();
