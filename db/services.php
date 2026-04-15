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
 * Web services for block_newcoursecontents.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_newcoursecontents_get_course_details' => [
        'classname' => 'block_newcoursecontents\\external',
        'methodname' => 'get_course_details',
        'classpath' => 'blocks/newcoursecontents/classes/external.php',
        'description' => 'Get course details with activities for modal display',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/course:view',
    ],
    'block_newcoursecontents_mark_seen' => [
        'classname' => 'block_newcoursecontents\\external',
        'methodname' => 'mark_activities_seen',
        'classpath' => 'blocks/newcoursecontents/classes/external.php',
        'description' => 'Mark all activities in a course as seen',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:view',
    ],
    'block_newcoursecontents_mark_activity_seen' => [
        'classname' => 'block_newcoursecontents\\external',
        'methodname' => 'mark_activity_seen',
        'classpath' => 'blocks/newcoursecontents/classes/external.php',
        'description' => 'Mark a single activity as seen',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:view',
    ],
];

$services = [
    'NewCourseContents Service' => [
        'functions' => array_keys($functions),
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
