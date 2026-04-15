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
 * Strings for component 'block_newcoursecontents', language 'en'.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'New Course Contents';
$string['pluginname_help'] = 'Displays recent course activity with badges showing new or changed content since your last visit.';

$string['addinstance'] = 'Add a new Course Contents block';
$string['myaddinstance'] = 'Add a new Course Contents block to Dashboard';

$string['nocourses'] = 'You are not enrolled in any visible courses.';
$string['nocourses_desc'] = 'Enroll in courses to see their activity here.';

$string['nevervisited'] = 'Never visited';

$string['viewdetails'] = 'View changes';
$string['clickfordetails'] = 'Click to see all changes since last visit';

$string['lastupdated'] = 'Click badges to see details';

$string['lastvisit'] = 'Last visit';
$string['lastactivity'] = 'Last activity';
$string['never'] = 'Never';

$string['nevervisited'] = 'Never visited';
$string['minutesago'] = '{$a} minutes ago';
$string['hoursago'] = '{$a} hours ago';
$string['daysago'] = '{$a} days ago';
$string['justnow'] = 'Just now';

$string['new'] = 'New';
$string['updated'] = 'Updated';
$string['modified'] = 'Modified';
$string['added'] = 'Added';
$string['open'] = 'Open';
$string['gotocourse'] = 'Go to course';
$string['gotocoursebutton'] = 'Go to course';
$string['gotodashboard'] = 'Back to Dashboard';
$string['markallseen'] = 'Mark all as seen';
$string['markasseen'] = 'Mark as seen';

$string['nochanges'] = 'No new or changed activities since your last visit.';
$string['nochanges_desc'] = 'Check back later for updates.';

$string['activity'] = 'Activity';
$string['activities'] = 'Activities';
$string['type'] = 'Type';
$string['title'] = 'Title';
$string['time'] = 'Time';

$string['detailtitle'] = 'Course Activity Details';
$string['backtodashboard'] = 'Back to Dashboard';

$string['courseinfo'] = 'Course Information';
$string['enrolledcourses'] = 'Enrolled Courses';
$string['hiddencourses'] = 'Hidden courses are not shown.';

$string['settings_header'] = 'New Course Contents Block Settings';
$string['settings_desc'] = 'Configure the behavior of the New Course Contents block.';

$string['cache_lifetime'] = 'Cache Lifetime';
$string['cache_lifetime_desc'] = 'How long should the activity data be cached (in minutes)?';
$string['cache_lifetime_default'] = '30';

$string['show_archived'] = 'Show Archived Courses';
$string['show_archived_desc'] = 'Include archived courses in the activity list.';

$string['badgecolor'] = 'Badge Color';
$string['badgecolor_desc'] = 'The background color for the activity badge (e.g., #0063A6).';

$string['privacy:metadata'] = 'The New Course Contents block stores information about your last visit to each course.';
$string['privacy:metadata:lastseen'] = 'The timestamp of your last visit to a specific course.';

$string['loading'] = 'Loading...';
$string['errorloading'] = 'Error loading course details';

$string['searchcourses'] = 'Search courses...';
$string['sortbylastaccessed'] = 'Sort by activity';
$string['sortbytitle'] = 'Sort by name';
$string['sortbyshortname'] = 'Sort by shortname';
$string['viewcard'] = 'Card view';
$string['viewlist'] = 'List view';

$string['allincludinghidden'] = 'All (including hidden)';
$string['all'] = 'All';
$string['inprogress'] = 'In progress';
$string['future'] = 'Future';
$string['past'] = 'Past';
$string['favourites'] = 'Starred';

$string['aria:groupingdropdown'] = 'Course grouping selector';
$string['aria:sortingdropdown'] = 'Course sorting selector';
$string['aria:allcoursesincludinghidden'] = 'Show all courses including hidden';
$string['aria:allcourses'] = 'Show all courses';
$string['aria:inprogress'] = 'Show in-progress courses';
$string['aria:future'] = 'Show future courses';
$string['aria:past'] = 'Show past courses';
$string['aria:favourites'] = 'Show starred courses';
$string['aria:controls'] = 'Course list controls';
$string['aria:newactivities'] = '{$a->count} new activities in {$a->coursename}';
$string['aria:viewchanges'] = 'View changes in {$a}';
$string['aria:nevervisited'] = 'Never visited this course';
$string['aria:lastvisited'] = 'Last visited {$a}';
$string['aria:courselist'] = 'Course list with {$a} courses';
$string['aria:clearsearch'] = 'Clear search';
