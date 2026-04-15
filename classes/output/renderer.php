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
 * Renderer for block_newcoursecontents.
 *
 * @package   block_newcoursecontents
 * @copyright 2026 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_newcoursecontents\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    /**
     * Render the block content.
     *
     * @param array $context Template context
     * @return string HTML
     */
    public function render_block(array $context) {
        return $this->render_from_template('block_newcoursecontents/block', $context);
    }

    /**
     * Render the detail page content.
     *
     * @param array $context Template context
     * @return string HTML
     */
    public function render_detail_page(array $context) {
        return $this->render_from_template('block_newcoursecontents/detail_page', $context);
    }

    /**
     * Render the modal content.
     *
     * @param array $context Template context
     * @return string HTML
     */
    public function render_modal_content(array $context) {
        return $this->render_from_template('block_newcoursecontents/modal_content', $context);
    }
}
