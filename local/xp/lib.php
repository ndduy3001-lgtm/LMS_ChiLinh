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
 * Library functions for local_xp.
 *
 * Includes navigation hooks to add leaderboard and XP links to Moodle's navigation.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the main navigation to add XP links.
 *
 * Adds "Leaderboard" and "My XP" links under the site navigation
 * when the XP system is enabled.
 *
 * @param global_navigation $nav The global navigation instance.
 */
function local_xp_extend_navigation(global_navigation $nav) {
    if (!get_config('local_xp', 'enabled')) {
        return;
    }

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();

    // Leaderboard link.
    if (has_capability('local/xp:viewleaderboard', $context)) {
        $nav->add(
            get_string('leaderboard', 'local_xp'),
            new moodle_url('/local/xp/leaderboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_xp_leaderboard',
            new pix_icon('i/report', '')
        );
    }

    // My XP link.
    if (has_capability('local/xp:viewownxp', $context)) {
        $nav->add(
            get_string('xppoints', 'local_xp'),
            new moodle_url('/local/xp/user_xp.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_xp_myxp',
            new pix_icon('i/star', '')
        );
    }
}

/**
 * Extend course navigation to add course-specific XP links.
 *
 * @param navigation_node $parentnode The parent node.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 */
function local_xp_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (!get_config('local_xp', 'enabled')) {
        return;
    }

    // Course leaderboard.
    if (has_capability('local/xp:viewleaderboard', $context)) {
        $parentnode->add(
            get_string('leaderboard', 'local_xp'),
            new moodle_url('/local/xp/leaderboard.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_xp_course_leaderboard',
            new pix_icon('i/report', '')
        );
    }

    // My XP in this course.
    if (has_capability('local/xp:viewownxp', $context)) {
        $parentnode->add(
            get_string('xppoints', 'local_xp'),
            new moodle_url('/local/xp/user_xp.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_xp_course_myxp',
            new pix_icon('i/star', '')
        );
    }
}
