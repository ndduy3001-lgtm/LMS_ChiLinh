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
 * Post-installation script for local_xp.
 *
 * Seeds the default XP rules into the database.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Executed after the plugin is installed.
 *
 * @return bool
 */
function xmldb_local_xp_install() {
    global $DB;

    $now = time();

    // Default XP rules (system-wide, courseid = 0).
    $defaultrules = [
        [
            'courseid'     => 0,
            'eventname'    => '\core\event\course_completed',
            'points'       => 200,
            'enabled'      => 1,
            'conditions'   => null,
            'timecreated'  => $now,
            'timemodified' => $now,
        ],
        [
            'courseid'     => 0,
            'eventname'    => '\core\event\course_module_completion_updated',
            'points'       => 20,
            'enabled'      => 1,
            'conditions'   => null,
            'timecreated'  => $now,
            'timemodified' => $now,
        ],
        [
            'courseid'     => 0,
            'eventname'    => '\core\event\user_graded',
            'points'       => 30,
            'enabled'      => 1,
            'conditions'   => json_encode(['min_grade_percent' => 50]),
            'timecreated'  => $now,
            'timemodified' => $now,
        ],
        [
            'courseid'     => 0,
            'eventname'    => '\core\event\badge_awarded',
            'points'       => 50,
            'enabled'      => 1,
            'conditions'   => null,
            'timecreated'  => $now,
            'timemodified' => $now,
        ],
    ];

    foreach ($defaultrules as $rule) {
        $DB->insert_record('local_xp_rules', (object) $rule);
    }

    return true;
}
