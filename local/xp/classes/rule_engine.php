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
 * Rule engine for the XP system.
 *
 * Manages XP rules: CRUD operations, evaluation, and matching events to point values.
 * Supports course-specific overrides with system-wide fallback.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_engine {

    /** @var array Cached list of supported events with metadata. */
    protected static array $supportedevents = [
        '\core\event\course_completed' => [
            'name' => 'course_completed',
            'description' => 'Course completed',
            'default_points' => 200,
            'category' => 'course',
        ],
        '\core\event\course_module_completion_updated' => [
            'name' => 'module_completed',
            'description' => 'Activity module completed',
            'default_points' => 20,
            'category' => 'activity',
        ],
        '\core\event\user_graded' => [
            'name' => 'grade_achieved',
            'description' => 'Grade achieved (with threshold)',
            'default_points' => 30,
            'category' => 'grade',
        ],
        '\core\event\badge_awarded' => [
            'name' => 'badge_earned',
            'description' => 'Badge earned',
            'default_points' => 50,
            'category' => 'badge',
        ],
    ];

    /**
     * Get all supported event types with their metadata.
     *
     * @return array Associative array keyed by event class name.
     */
    public static function get_supported_events(): array {
        return self::$supportedevents;
    }

    /**
     * Get all rules, optionally filtered by course.
     *
     * @param int $courseid Filter by course (0 = system-wide rules only).
     * @return array Array of rule records.
     */
    public function get_rules(int $courseid = 0): array {
        global $DB;

        if ($courseid > 0) {
            // Get both course-specific and system-wide rules.
            return $DB->get_records_select(
                'local_xp_rules',
                'courseid = :courseid OR courseid = 0',
                ['courseid' => $courseid],
                'courseid ASC, eventname ASC'
            );
        }

        return $DB->get_records('local_xp_rules', ['courseid' => 0], 'eventname ASC');
    }

    /**
     * Get a single rule by ID.
     *
     * @param int $ruleid The rule ID.
     * @return \stdClass|false The rule record or false.
     */
    public function get_rule(int $ruleid) {
        global $DB;
        return $DB->get_record('local_xp_rules', ['id' => $ruleid]);
    }

    /**
     * Create a new XP rule.
     *
     * @param string $eventname The event class name.
     * @param int $points XP to award.
     * @param int $courseid Course ID (0 for system-wide).
     * @param bool $enabled Whether the rule is active.
     * @param array|null $conditions Extra conditions (JSON-encoded).
     * @return int The new rule ID.
     */
    public function create_rule(
        string $eventname,
        int $points,
        int $courseid = 0,
        bool $enabled = true,
        ?array $conditions = null
    ): int {
        global $DB;

        $now = time();
        $rule = new \stdClass();
        $rule->courseid = $courseid;
        $rule->eventname = $eventname;
        $rule->points = $points;
        $rule->enabled = $enabled ? 1 : 0;
        $rule->conditions = $conditions ? json_encode($conditions) : null;
        $rule->timecreated = $now;
        $rule->timemodified = $now;

        return $DB->insert_record('local_xp_rules', $rule);
    }

    /**
     * Update an existing rule.
     *
     * @param int $ruleid Rule ID to update.
     * @param array $data Associative array of fields to update.
     * @return bool True on success.
     */
    public function update_rule(int $ruleid, array $data): bool {
        global $DB;

        $rule = $DB->get_record('local_xp_rules', ['id' => $ruleid]);
        if (!$rule) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (property_exists($rule, $key) && $key !== 'id') {
                if ($key === 'conditions' && is_array($value)) {
                    $rule->$key = json_encode($value);
                } else {
                    $rule->$key = $value;
                }
            }
        }
        $rule->timemodified = time();

        return $DB->update_record('local_xp_rules', $rule);
    }

    /**
     * Delete a rule by ID.
     *
     * @param int $ruleid Rule ID to delete.
     * @return bool True on success.
     */
    public function delete_rule(int $ruleid): bool {
        global $DB;
        return $DB->delete_records('local_xp_rules', ['id' => $ruleid]);
    }

    /**
     * Toggle a rule's enabled status.
     *
     * @param int $ruleid Rule ID.
     * @return bool The new enabled state.
     */
    public function toggle_rule(int $ruleid): bool {
        global $DB;

        $rule = $DB->get_record('local_xp_rules', ['id' => $ruleid]);
        if (!$rule) {
            return false;
        }

        $rule->enabled = $rule->enabled ? 0 : 1;
        $rule->timemodified = time();
        $DB->update_record('local_xp_rules', $rule);

        return (bool) $rule->enabled;
    }

    /**
     * Get the best matching rule for an event in a course context.
     *
     * Course-specific rules take priority over system-wide rules.
     *
     * @param string $eventname The event class name.
     * @param int $courseid The course ID.
     * @return \stdClass|false The matching rule or false.
     */
    public function match_event(string $eventname, int $courseid = 0) {
        global $DB;

        // Try course-specific rule first.
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

    /**
     * Evaluate conditions for a rule against event data.
     *
     * @param \stdClass $rule The rule with conditions.
     * @param \core\event\base $event The event to evaluate.
     * @return bool True if conditions are met (or no conditions).
     */
    public function evaluate_conditions(\stdClass $rule, \core\event\base $event): bool {
        if (empty($rule->conditions)) {
            return true;
        }

        $conditions = json_decode($rule->conditions, true);
        if (!is_array($conditions)) {
            return true;
        }

        // Evaluate min_grade_percent condition.
        if (isset($conditions['min_grade_percent']) && $event instanceof \core\event\user_graded) {
            return $this->check_grade_condition($event, (float) $conditions['min_grade_percent']);
        }

        return true;
    }

    /**
     * Check if a grade event meets a minimum percentage threshold.
     *
     * @param \core\event\user_graded $event The grade event.
     * @param float $minpercent Minimum grade percentage.
     * @return bool True if the grade meets the threshold.
     */
    protected function check_grade_condition(\core\event\user_graded $event, float $minpercent): bool {
        global $DB;

        try {
            $gradeitem = $DB->get_record('grade_items', ['id' => $event->other['itemid'] ?? 0]);
            if (!$gradeitem || empty($gradeitem->grademax) || $gradeitem->grademax <= 0) {
                return false;
            }

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
            return false;
        }
    }

    /**
     * Reset all rules to default values.
     *
     * @return void
     */
    public function reset_to_defaults(): void {
        global $DB;

        // Delete all system-wide rules.
        $DB->delete_records('local_xp_rules', ['courseid' => 0]);

        // Re-seed defaults.
        $now = time();
        foreach (self::$supportedevents as $eventname => $meta) {
            $conditions = null;
            if ($eventname === '\core\event\user_graded') {
                $conditions = json_encode(['min_grade_percent' => 50]);
            }

            $this->create_rule(
                $eventname,
                $meta['default_points'],
                0,
                true,
                $conditions ? json_decode($conditions, true) : null
            );
        }
    }
}
