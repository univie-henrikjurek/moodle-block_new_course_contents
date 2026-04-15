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
 * The New Course Contents block.
 *
 * Displays recent course activity with badges for new/changed content.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_newcoursecontents extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_newcoursecontents');
    }

    public function get_content() {
        global $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        try {
            $courses = \block_newcoursecontents\manager::get_courses_with_activities($USER->id);

            $badgecolor = get_config('block_newcoursecontents', 'badgecolor');
            if (empty($badgecolor)) {
                $badgecolor = '#0063A6';
            }

            foreach ($courses as &$course) {
                if (is_object($course['courseurl'])) {
                    $course['courseurl'] = $course['courseurl']->out(false);
                }
                if (is_object($course['detailurl'])) {
                    $course['detailurl'] = $course['detailurl']->out(false);
                }
                $course['viewurl'] = $course['courseurl'];
                $course['badgecolor'] = $badgecolor;
                
                if ($course['lastactivity']) {
                    $course['lasttimestr'] = \block_newcoursecontents\manager::format_time($course['lastactivity']);
                } else {
                    $course['lasttimestr'] = null;
                }
                
                if ($course['lastseen']) {
                    $course['lastvisitstr'] = \block_newcoursecontents\manager::format_time($course['lastseen']);
                } else {
                    $course['lastvisitstr'] = null;
                }
                
                $course['hasnewactivity'] = ($course['activitycount'] > 0);
            }

            $templatecontext = [
                'courses' => $courses,
            ];

            $renderer = $PAGE->get_renderer('block_newcoursecontents');
            $this->content->text = $renderer->render_block($templatecontext);
        } catch (Exception $e) {
            $this->content->text = '<div class="alert alert-danger">Error loading course activities: ' . 
                htmlspecialchars($e->getMessage()) . '</div>';
            error_log('block_newcoursecontents error: ' . $e->getMessage());
            error_log('block_newcoursecontents trace: ' . $e->getTraceAsString());
        }

        return $this->content;
    }

    public function applicable_formats() {
        return [
            'my' => true,
            'site' => false,
        ];
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function instance_allow_config() {
        return false;
    }

    public function get_aria_role() {
        return 'complementary';
    }

    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = format_string($this->config->title);
        } else {
            $this->title = get_string('pluginname', 'block_newcoursecontents');
        }
    }

    public function instance_delete() {
        global $USER;
        
        require_once(__DIR__ . '/classes/manager.php');
        \block_newcoursecontents\manager::clear_cache($USER->id);
        
        return true;
    }
}
