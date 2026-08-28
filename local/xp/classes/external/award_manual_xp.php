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
 * External function to manually award XP to a user.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_manual_xp extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID to award XP to'),
            'courseid' => new external_value(PARAM_INT, 'Course ID (0 = system-wide)', VALUE_DEFAULT, 0),
            'points' => new external_value(PARAM_INT, 'Number of XP to award'),
            'reason' => new external_value(PARAM_TEXT, 'Reason for awarding XP', VALUE_DEFAULT, 'manual'),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @param int $points XP to award.
     * @param string $reason Reason for awarding.
     * @return array Result with success status.
     */
    public static function execute(int $userid, int $courseid = 0, int $points = 0, string $reason = 'manual'): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'courseid' => $courseid,
            'points' => $points,
            'reason' => $reason,
        ]);

        // Context and capability check.
        if ($params['courseid'] > 0) {
            $context = \context_course::instance($params['courseid']);
        } else {
            $context = \context_system::instance();
        }
        self::validate_context($context);
        require_capability('local/xp:awardxp', $context);

        if ($params['points'] <= 0) {
            return [
                'success' => false,
                'message' => get_string('invalid_points', 'local_xp'),
                'newpoints' => 0,
                'newlevel' => 0,
            ];
        }

        $manager = new \local_xp\manager();
        $result = $manager->award_points(
            $params['userid'],
            $params['courseid'],
            $params['points'],
            $params['reason'],
            'manual_award',
            $context->id,
            0
        );

        if ($result) {
            // Get updated XP data.
            $calculator = $manager->get_level_calculator();
            if ($params['courseid'] > 0) {
                $xprecord = $manager->get_user_xp($params['userid'], $params['courseid']);
                $newpoints = $xprecord ? (int) $xprecord->points : 0;
            } else {
                $newpoints = $manager->get_user_total_xp($params['userid']);
            }
            $newlevel = $calculator->calculate_level($newpoints);

            return [
                'success' => true,
                'message' => get_string('xp_awarded_success', 'local_xp', $params['points']),
                'newpoints' => $newpoints,
                'newlevel' => $newlevel,
            ];
        }

        return [
            'success' => false,
            'message' => get_string('xp_award_failed', 'local_xp'),
            'newpoints' => 0,
            'newlevel' => 0,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the award was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'newpoints' => new external_value(PARAM_INT, 'Updated total XP'),
            'newlevel' => new external_value(PARAM_INT, 'Updated level'),
        ]);
    }
}
