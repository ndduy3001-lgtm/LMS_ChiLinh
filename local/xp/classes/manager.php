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
 * XP Manager - Core business logic for the XP system.
 *
 * Handles awarding XP, deduplication, level recalculation,
 * and querying user XP records.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var level_calculator Level calculator instance. */
    protected level_calculator $levelcalculator;

    /**
     * Constructor.
     *
     * @param level_calculator|null $levelcalculator Optional level calculator.
     */
    public function __construct(?level_calculator $levelcalculator = null) {
        $this->levelcalculator = $levelcalculator ?? new level_calculator();
    }

    /**
     * Award XP points to a user.
     *
     * This is the primary method for granting XP. It handles:
     * - Deduplication (prevents awarding XP for the same event twice)
     * - Logging the XP award in the audit trail
     * - Updating the user's total XP and recalculating their level
     *
     * @param int $userid The user receiving XP.
     * @param int $courseid The course context (0 for system-wide).
     * @param int $points Number of XP to award.
     * @param string $reason Human-readable reason (e.g. 'course_completed').
     * @param string $eventname Full event class name.
     * @param int $contextid Context ID for deduplication.
     * @param int $objectid Object ID for deduplication.
     * @return bool True if XP was awarded, false if duplicate or error.
     */
    public function award_points(
        int $userid,
        int $courseid,
        int $points,
        string $reason,
        string $eventname = '',
        int $contextid = 0,
        int $objectid = 0
    ): bool {
        global $DB;

        if ($points <= 0) {
            return false;
        }

        // Check for plugin enabled status.
        if (!$this->is_enabled()) {
            return false;
        }

        // Deduplication: check if XP was already awarded for this exact event.
        if (!empty($eventname) && $contextid > 0 && $objectid > 0) {
            if ($this->is_duplicate($userid, $eventname, $contextid, $objectid)) {
                return false;
            }
        }

        // Use a transaction to ensure atomicity.
        $transaction = $DB->start_delegated_transaction();

        try {
            // 1. Log the XP award.
            $log = new \stdClass();
            $log->userid = $userid;
            $log->courseid = $courseid;
            $log->points = $points;
            $log->reason = $reason;
            $log->eventname = $eventname;
            $log->contextid = $contextid;
            $log->objectid = $objectid;
            $log->timecreated = time();
            $DB->insert_record('local_xp_log', $log);

            // 2. Update or create the user's XP total.
            $this->update_user_points($userid, $courseid, $points);

            $transaction->allow_commit();
            return true;

        } catch (\Exception $e) {
            $transaction->rollback($e);
            debugging('local_xp: Failed to award XP - ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Update the user's XP total and recalculate level.
     *
     * Creates the record if it doesn't exist, otherwise increments.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID (0 for system-wide).
     * @param int $pointstoadd Points to add.
     */
    protected function update_user_points(int $userid, int $courseid, int $pointstoadd): void {
        global $DB;

        $now = time();

        // Try to get existing record.
        $record = $DB->get_record('local_xp_points', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        if ($record) {
            // Update existing.
            $record->points += $pointstoadd;
            $record->level = $this->levelcalculator->calculate_level($record->points);
            $record->timemodified = $now;
            $DB->update_record('local_xp_points', $record);
        } else {
            // Create new.
            $record = new \stdClass();
            $record->userid = $userid;
            $record->courseid = $courseid;
            $record->points = $pointstoadd;
            $record->level = $this->levelcalculator->calculate_level($pointstoadd);
            $record->timecreated = $now;
            $record->timemodified = $now;
            $DB->insert_record('local_xp_points', $record);
        }
    }

    /**
     * Check if XP has already been awarded for a specific event.
     *
     * @param int $userid User ID.
     * @param string $eventname Event class name.
     * @param int $contextid Context ID.
     * @param int $objectid Object ID.
     * @return bool True if this is a duplicate.
     */
    public function is_duplicate(int $userid, string $eventname, int $contextid, int $objectid): bool {
        global $DB;

        return $DB->record_exists('local_xp_log', [
            'userid' => $userid,
            'eventname' => $eventname,
            'contextid' => $contextid,
            'objectid' => $objectid,
        ]);
    }

    /**
     * Get a user's XP record for a specific course.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID (0 for system-wide).
     * @return \stdClass|false The XP record, or false if not found.
     */
    public function get_user_xp(int $userid, int $courseid = 0) {
        global $DB;

        return $DB->get_record('local_xp_points', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
    }

    /**
     * Get a user's total XP across all courses.
     *
     * @param int $userid User ID.
     * @return int Total XP points.
     */
    public function get_user_total_xp(int $userid): int {
        global $DB;

        $total = $DB->get_field_sql(
            "SELECT COALESCE(SUM(points), 0) FROM {local_xp_points} WHERE userid = :userid",
            ['userid' => $userid]
        );

        return (int) $total;
    }

    /**
     * Get the XP log (history) for a user.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID (0 for all courses).
     * @param int $limitfrom Offset for pagination.
     * @param int $limitnum Number of records.
     * @return array Array of log records.
     */
    public function get_user_log(int $userid, int $courseid = 0, int $limitfrom = 0, int $limitnum = 50): array {
        global $DB;

        $params = ['userid' => $userid];
        $where = 'userid = :userid';

        if ($courseid > 0) {
            $where .= ' AND courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        return $DB->get_records_select(
            'local_xp_log',
            $where,
            $params,
            'timecreated DESC',
            '*',
            $limitfrom,
            $limitnum
        );
    }

    /**
     * Get the level calculator instance.
     *
     * @return level_calculator
     */
    public function get_level_calculator(): level_calculator {
        return $this->levelcalculator;
    }

    /**
     * Check if the XP system is enabled.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('local_xp', 'enabled');
    }

    /**
     * Get an XP rule matching the given event for a course (or system-wide fallback).
     *
     * Course-specific rules take priority over system-wide rules.
     *
     * @param string $eventname The full event class name.
     * @param int $courseid The course ID.
     * @return \stdClass|false The matching rule, or false.
     */
    public function get_rule_for_event(string $eventname, int $courseid = 0) {
        global $DB;

        // First try course-specific rule.
        if ($courseid > 0) {
            $rule = $DB->get_record('local_xp_rules', [
                'eventname' => $eventname,
                'courseid' => $courseid,
                'enabled' => 1,
            ]);
            if ($rule) {
                return $rule;
            }
        }

        // Fall back to system-wide rule.
        return $DB->get_record('local_xp_rules', [
            'eventname' => $eventname,
            'courseid' => 0,
            'enabled' => 1,
        ]);
    }
}
