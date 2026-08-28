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
 * Event observers for local_xp.
 *
 * Registers callbacks for core Moodle events to trigger XP awards.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Award XP when a user completes a course.
    [
        'eventname' => '\core\event\course_completed',
        'callback'  => '\local_xp\observer::course_completed',
    ],

    // Award XP when a user completes an activity module.
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback'  => '\local_xp\observer::module_completion_updated',
    ],

    // Award XP when a user receives a grade.
    [
        'eventname' => '\core\event\user_graded',
        'callback'  => '\local_xp\observer::user_graded',
    ],

    // Award XP when a user earns a badge.
    [
        'eventname' => '\core\event\badge_awarded',
        'callback'  => '\local_xp\observer::badge_awarded',
    ],
];
