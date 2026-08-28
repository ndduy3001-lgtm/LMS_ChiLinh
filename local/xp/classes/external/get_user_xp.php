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

namespace local_xp\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function to get a user's XP, level, and progress.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_xp extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID (0 = current user)', VALUE_DEFAULT, 0),
            'courseid' => new external_value(PARAM_INT, 'Course ID (0 = system-wide)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return array User XP data.
     */
    public static function execute(int $userid = 0, int $courseid = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        $userid = $params['userid'] ?: $USER->id;
        $courseid = $params['courseid'];

        // Context and capability check.
        if ($courseid > 0) {
            $context = \context_course::instance($courseid);
        } else {
            $context = \context_system::instance();
        }
        self::validate_context($context);

        if ($userid != $USER->id) {
            require_capability('local/xp:viewallxp', $context);
        } else {
            require_capability('local/xp:viewownxp', $context);
        }

        $manager = new \local_xp\manager();
        $calculator = $manager->get_level_calculator();

        if ($courseid > 0) {
            $xprecord = $manager->get_user_xp($userid, $courseid);
            $points = $xprecord ? (int) $xprecord->points : 0;
        } else {
            $points = $manager->get_user_total_xp($userid);
        }

        $level = $calculator->calculate_level($points);
        $progress = $calculator->get_level_progress($points);
        $nextthreshold = $calculator->get_level_threshold($level + 1);
        $currentthreshold = $calculator->get_level_threshold($level);
        $maxlevel = $calculator->get_max_level();

        return [
            'userid' => $userid,
            'courseid' => $courseid,
            'points' => $points,
            'level' => $level,
            'progress' => round($progress, 1),
            'xptonextlevel' => ($level < $maxlevel) ? ($nextthreshold - $points) : 0,
            'currentthreshold' => $currentthreshold,
            'nextthreshold' => $nextthreshold,
            'maxlevel' => $maxlevel,
            'ismaxlevel' => ($level >= $maxlevel),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'points' => new external_value(PARAM_INT, 'Total XP points'),
            'level' => new external_value(PARAM_INT, 'Current level'),
            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage to next level'),
            'xptonextlevel' => new external_value(PARAM_INT, 'XP needed for next level'),
            'currentthreshold' => new external_value(PARAM_INT, 'XP threshold for current level'),
            'nextthreshold' => new external_value(PARAM_INT, 'XP threshold for next level'),
            'maxlevel' => new external_value(PARAM_INT, 'Maximum achievable level'),
            'ismaxlevel' => new external_value(PARAM_BOOL, 'Whether user is at max level'),
        ]);
    }
}
