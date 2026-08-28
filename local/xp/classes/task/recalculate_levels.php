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

namespace local_xp\task;

/**
 * Scheduled task to recalculate all user levels.
 *
 * Ensures level values in local_xp_points stay consistent with
 * the current level calculation formula. Useful after changing
 * the base XP or max level configuration.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recalculate_levels extends \core\task\scheduled_task {

    /**
     * Get the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_recalculate_levels', 'local_xp');
    }

    /**
     * Execute the task.
     *
     * Iterates over all local_xp_points records and recalculates the level
     * based on the current points using the level calculator.
     */
    public function execute(): void {
        global $DB;

        if (!get_config('local_xp', 'enabled')) {
            mtrace('XP system is disabled. Skipping level recalculation.');
            return;
        }

        $calculator = new \local_xp\level_calculator();

        // Process in batches to avoid memory issues.
        $batchsize = 1000;
        $offset = 0;
        $updated = 0;

        while (true) {
            $records = $DB->get_records('local_xp_points', null, 'id ASC', '*', $offset, $batchsize);

            if (empty($records)) {
                break;
            }

            foreach ($records as $record) {
                $newlevel = $calculator->calculate_level((int) $record->points);

                if ((int) $record->level !== $newlevel) {
                    $record->level = $newlevel;
                    $record->timemodified = time();
                    $DB->update_record('local_xp_points', $record);
                    $updated++;
                }
            }

            $offset += $batchsize;

            if (count($records) < $batchsize) {
                break;
            }
        }

        mtrace("Level recalculation complete. Updated {$updated} record(s).");
    }
}
