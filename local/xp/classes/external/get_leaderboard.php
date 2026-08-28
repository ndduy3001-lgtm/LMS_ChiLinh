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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function to get the leaderboard.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_leaderboard extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID (0 = system-wide)', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Number of results', VALUE_DEFAULT, 10),
            'offset' => new external_value(PARAM_INT, 'Offset for pagination', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $courseid Course ID.
     * @param int $limit Number of results.
     * @param int $offset Offset for pagination.
     * @return array Leaderboard data.
     */
    public static function execute(int $courseid = 0, int $limit = 10, int $offset = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $courseid = $params['courseid'];
        $limit = min($params['limit'], 100); // Cap at 100.
        $offset = $params['offset'];

        // Context and capability check.
        if ($courseid > 0) {
            $context = \context_course::instance($courseid);
        } else {
            $context = \context_system::instance();
        }
        self::validate_context($context);
        require_capability('local/xp:viewleaderboard', $context);

        $leaderboard = new \local_xp\leaderboard();

        if ($courseid > 0) {
            $entries = $leaderboard->get_course_leaderboard($courseid, $limit, $offset);
        } else {
            $entries = $leaderboard->get_system_leaderboard($limit, $offset);
        }

        $result = [];
        $rank = $offset + 1;
        foreach ($entries as $entry) {
            $result[] = [
                'rank' => $rank++,
                'userid' => (int) $entry->userid,
                'firstname' => $entry->firstname,
                'lastname' => $entry->lastname,
                'points' => (int) $entry->points,
                'level' => (int) ($entry->level ?? $entry->max_level ?? 1),
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'rank' => new external_value(PARAM_INT, 'Leaderboard rank'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'firstname' => new external_value(PARAM_TEXT, 'First name'),
                'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                'points' => new external_value(PARAM_INT, 'Total XP points'),
                'level' => new external_value(PARAM_INT, 'Current level'),
            ])
        );
    }
}
