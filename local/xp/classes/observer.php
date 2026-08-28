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

use core\event\course_completed;
use core\event\course_module_completion_updated;
use core\event\user_graded;
use core\event\badge_awarded;

/**
 * Event observer for local_xp.
 *
 * Listens to core Moodle events and delegates XP awarding to the manager.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle course completion event.
     *
     * Awards XP when a user completes an entire course.
     *
     * @param course_completed $event The event.
     */
    public static function course_completed(course_completed $event): void {
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        if (!self::can_earn_xp($userid, $courseid)) {
            return;
        }

        $manager = self::get_manager();
        $rule = $manager->get_rule_for_event('\core\event\course_completed', $courseid);

        if (!$rule) {
            return;
        }

        $manager->award_points(
            $userid,
            $courseid,
            (int) $rule->points,
            'course_completed',
            '\core\event\course_completed',
            $event->contextid,
            $event->objectid
        );
    }

    /**
     * Handle course module completion event.
     *
     * Awards XP when a user completes an individual activity module.
     * Only awards XP when the completion state transitions to COMPLETE or COMPLETE_PASS.
     *
     * @param course_module_completion_updated $event The event.
     */
    public static function module_completion_updated(course_module_completion_updated $event): void {
        // Only award XP for completion, not for "incomplete" state changes.
        $completionstate = $event->other['completionstate'] ?? null;
        if ($completionstate === null) {
            return;
        }

        // COMPLETION_COMPLETE = 1, COMPLETION_COMPLETE_PASS = 2.
        if (!in_array((int) $completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
            return;
        }

        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        if (!self::can_earn_xp($userid, $courseid)) {
            return;
        }

        $manager = self::get_manager();
        $rule = $manager->get_rule_for_event(
            '\core\event\course_module_completion_updated',
            $courseid
        );

        if (!$rule) {
            return;
        }

        $manager->award_points(
            $userid,
            $courseid,
            (int) $rule->points,
            'module_completed',
            '\core\event\course_module_completion_updated',
            $event->contextid,
            $event->objectid
        );
    }

    /**
     * Handle user graded event.
     *
     * Awards bonus XP when a user receives a grade above the configured threshold.
     * Only triggers on final grades (not category/course grades).
     *
     * @param user_graded $event The event.
     */
    public static function user_graded(user_graded $event): void {
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        if (!self::can_earn_xp($userid, $courseid)) {
            return;
        }

        $manager = self::get_manager();
        $rule = $manager->get_rule_for_event('\core\event\user_graded', $courseid);

        if (!$rule) {
            return;
        }

        // Check grade threshold condition if configured.
        if (!empty($rule->conditions)) {
            $conditions = json_decode($rule->conditions, true);
            if (!empty($conditions['min_grade_percent'])) {
                if (!self::meets_grade_threshold($event, (float) $conditions['min_grade_percent'])) {
                    return;
                }
            }
        }

        $manager->award_points(
            $userid,
            $courseid,
            (int) $rule->points,
            'grade_achieved',
            '\core\event\user_graded',
            $event->contextid,
            $event->objectid
        );
    }

    /**
     * Handle badge awarded event.
     *
     * Awards XP when a user earns a badge.
     *
     * @param badge_awarded $event The event.
     */
    public static function badge_awarded(badge_awarded $event): void {
        $userid = $event->relateduserid;
        $courseid = $event->courseid ?? 0;

        if (!self::can_earn_xp($userid, $courseid)) {
            return;
        }

        $manager = self::get_manager();
        $rule = $manager->get_rule_for_event('\core\event\badge_awarded', $courseid);

        if (!$rule) {
            return;
        }

        $manager->award_points(
            $userid,
            $courseid,
            (int) $rule->points,
            'badge_earned',
            '\core\event\badge_awarded',
            $event->contextid,
            $event->objectid
        );
    }

    /**
     * Check if a user has the capability to earn XP in a course.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return bool True if user can earn XP.
     */
    protected static function can_earn_xp(int $userid, int $courseid): bool {
        try {
            if ($courseid > 0) {
                $context = \context_course::instance($courseid, IGNORE_MISSING);
            } else {
                $context = \context_system::instance();
            }

            if (!$context) {
                return false;
            }

            return has_capability('local/xp:earnxp', $context, $userid);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a grade event meets the minimum grade threshold.
     *
     * @param user_graded $event The grade event.
     * @param float $minpercent Minimum grade percentage required.
     * @return bool True if the grade meets the threshold.
     */
    protected static function meets_grade_threshold(user_graded $event, float $minpercent): bool {
        global $DB;

        try {
            $gradeitem = $DB->get_record('grade_items', ['id' => $event->other['itemid'] ?? 0]);
            if (!$gradeitem || empty($gradeitem->grademax) || $gradeitem->grademax <= 0) {
                return false;
            }

            // Skip category and course grade items; only process activity grades.
            if ($gradeitem->itemtype !== 'mod') {
                return false;
            }

            $grade = $DB->get_record('grade_grades', ['id' => $event->objectid]);
            if (!$grade || $grade->finalgrade === null) {
                return false;
            }

            $percent = ($grade->finalgrade / $gradeitem->grademax) * 100;
            return $percent >= $minpercent;

        } catch (\Exception $e) {
            debugging('local_xp: Grade threshold check failed - ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get the XP manager singleton.
     *
     * @return manager
     */
    protected static function get_manager(): manager {
        static $instance = null;
        if ($instance === null) {
            $instance = new manager();
        }
        return $instance;
    }
}
