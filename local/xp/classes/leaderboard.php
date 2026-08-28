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

namespace local_xp;

/**
 * Leaderboard data provider for the XP system.
 *
 * Handles querying, ranking, and formatting leaderboard data
 * for both course-specific and system-wide views.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class leaderboard {

    /** @var int Number of entries per page. */
    protected int $perpage;

    /**
     * Constructor.
     *
     * @param int $perpage Number of results per page.
     */
    public function __construct(int $perpage = 20) {
        $this->perpage = $perpage;
    }

    /**
     * Get the leaderboard for a specific course.
     *
     * @param int $courseid Course ID (0 for system-wide).
     * @param int $page Page number (0-indexed).
     * @param int $perpage Results per page (0 = use default).
     * @return array ['entries' => [...], 'totalcount' => int]
     */
    public function get_leaderboard(int $courseid = 0, int $page = 0, int $perpage = 0): array {
        global $DB;

        if ($perpage <= 0) {
            $perpage = $this->perpage;
        }

        $offset = $page * $perpage;

        if ($courseid > 0) {
            return $this->get_course_leaderboard($courseid, $offset, $perpage);
        } else {
            return $this->get_system_leaderboard($offset, $perpage);
        }
    }

    /**
     * Get leaderboard for a specific course.
     *
     * @param int $courseid Course ID.
     * @param int $offset Query offset.
     * @param int $limit Query limit.
     * @return array ['entries' => [...], 'totalcount' => int]
     */
    protected function get_course_leaderboard(int $courseid, int $offset, int $limit): array {
        global $DB;

        $sql = "SELECT xp.id, xp.userid, xp.points, xp.level,
                       u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {local_xp_points} xp
                  JOIN {user} u ON u.id = xp.userid
                 WHERE xp.courseid = :courseid
                   AND u.deleted = 0
                   AND u.suspended = 0
                   AND xp.points > 0
              ORDER BY xp.points DESC, xp.timemodified ASC";

        $params = ['courseid' => $courseid];

        $entries = $DB->get_records_sql($sql, $params, $offset, $limit);

        // Add rank numbers.
        $entries = $this->add_ranks($entries, $offset);

        // Count total.
        $countsql = "SELECT COUNT(xp.id)
                       FROM {local_xp_points} xp
                       JOIN {user} u ON u.id = xp.userid
                      WHERE xp.courseid = :courseid
                        AND u.deleted = 0
                        AND u.suspended = 0
                        AND xp.points > 0";

        $totalcount = $DB->count_records_sql($countsql, $params);

        return [
            'entries' => array_values($entries),
            'totalcount' => (int) $totalcount,
        ];
    }

    /**
     * Get system-wide leaderboard (sum of all courses).
     *
     * @param int $offset Query offset.
     * @param int $limit Query limit.
     * @return array ['entries' => [...], 'totalcount' => int]
     */
    protected function get_system_leaderboard(int $offset, int $limit): array {
        global $DB;

        $sql = "SELECT u.id AS userid, SUM(xp.points) AS points, MAX(xp.level) AS level,
                       u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {local_xp_points} xp
                  JOIN {user} u ON u.id = xp.userid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND xp.points > 0
              GROUP BY u.id, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
              ORDER BY points DESC";

        $entries = $DB->get_records_sql($sql, [], $offset, $limit);

        // Recalculate levels for aggregated totals.
        $levelcalc = new level_calculator();
        foreach ($entries as $entry) {
            $entry->level = $levelcalc->calculate_level((int) $entry->points);
        }

        // Add rank numbers.
        $entries = $this->add_ranks($entries, $offset);

        // Count total unique users with XP.
        $countsql = "SELECT COUNT(DISTINCT xp.userid)
                       FROM {local_xp_points} xp
                       JOIN {user} u ON u.id = xp.userid
                      WHERE u.deleted = 0
                        AND u.suspended = 0
                        AND xp.points > 0";

        $totalcount = $DB->count_records_sql($countsql);

        return [
            'entries' => array_values($entries),
            'totalcount' => (int) $totalcount,
        ];
    }

    /**
     * Get the rank/position of a specific user.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID (0 for system-wide).
     * @return int The user's rank (1-based), or 0 if not found.
     */
    public function get_user_rank(int $userid, int $courseid = 0): int {
        global $DB;

        if ($courseid > 0) {
            // Get user's points in this course.
            $userpoints = $DB->get_field('local_xp_points', 'points', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);

            if ($userpoints === false) {
                return 0;
            }

            // Count users with more points.
            $sql = "SELECT COUNT(xp.id)
                      FROM {local_xp_points} xp
                      JOIN {user} u ON u.id = xp.userid
                     WHERE xp.courseid = :courseid
                       AND u.deleted = 0
                       AND u.suspended = 0
                       AND xp.points > :points";

            $rank = $DB->count_records_sql($sql, [
                'courseid' => $courseid,
                'points' => (int) $userpoints,
            ]);

            return $rank + 1;
        } else {
            // System-wide: sum all course points.
            $usertotal = $DB->get_field_sql(
                "SELECT COALESCE(SUM(points), 0) FROM {local_xp_points} WHERE userid = :userid",
                ['userid' => $userid]
            );

            if ((int) $usertotal <= 0) {
                return 0;
            }

            // Count users with higher total.
            $sql = "SELECT COUNT(*)
                      FROM (
                          SELECT xp.userid, SUM(xp.points) AS total
                            FROM {local_xp_points} xp
                            JOIN {user} u ON u.id = xp.userid
                           WHERE u.deleted = 0 AND u.suspended = 0
                        GROUP BY xp.userid
                          HAVING SUM(xp.points) > :usertotal
                      ) ranked";

            $rank = $DB->count_records_sql($sql, ['usertotal' => (int) $usertotal]);
            return $rank + 1;
        }
    }

    /**
     * Add rank numbers to leaderboard entries.
     *
     * @param array $entries Leaderboard entries.
     * @param int $offset The query offset (to calculate correct rank).
     * @return array Entries with 'rank' property added.
     */
    protected function add_ranks(array $entries, int $offset = 0): array {
        $rank = $offset + 1;
        foreach ($entries as $entry) {
            $entry->rank = $rank;
            $rank++;
        }
        return $entries;
    }

    /**
     * Get a compact leaderboard (e.g. for block display).
     *
     * @param int $courseid Course ID (0 for system-wide).
     * @param int $limit Number of entries (default 10).
     * @return array Array of leaderboard entries.
     */
    public function get_top(int $courseid = 0, int $limit = 10): array {
        $result = $this->get_leaderboard($courseid, 0, $limit);
        return $result['entries'];
    }
}
