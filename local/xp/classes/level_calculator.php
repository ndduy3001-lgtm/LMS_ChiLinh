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
 * Level calculator for the XP system.
 *
 * Computes the user level based on their total accumulated XP points.
 * Uses a quadratic progression formula: threshold(n) = base * n * (n + 1) / 2
 *
 * Default levels:
 *   Level 1:    0 -   99 XP
 *   Level 2:  100 -  299 XP
 *   Level 3:  300 -  599 XP
 *   Level 4:  600 -  999 XP
 *   Level 5: 1000 - 1499 XP
 *   ...
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class level_calculator {

    /** @var int Base XP increment per level. */
    protected int $base;

    /** @var int Maximum achievable level. */
    protected int $maxlevel;

    /**
     * Constructor.
     *
     * @param int $base Base XP increment between levels (default 100).
     * @param int $maxlevel Maximum level cap (default 50).
     */
    public function __construct(int $base = 100, int $maxlevel = 50) {
        $this->base = $base;
        $this->maxlevel = $maxlevel;
    }

    /**
     * Calculate the level for a given number of XP points.
     *
     * @param int $points Total XP points.
     * @return int The computed level (1-based).
     */
    public function calculate_level(int $points): int {
        if ($points < 0) {
            return 1;
        }

        $level = 1;
        while ($level < $this->maxlevel) {
            $threshold = $this->get_level_threshold($level + 1);
            if ($points < $threshold) {
                break;
            }
            $level++;
        }

        return $level;
    }

    /**
     * Get the minimum XP required to reach a specific level.
     *
     * Uses quadratic formula: threshold(n) = base * n * (n - 1) / 2
     * Level 1 = 0 XP, Level 2 = 100 XP, Level 3 = 300 XP, etc.
     *
     * @param int $level The target level.
     * @return int Minimum XP needed.
     */
    public function get_level_threshold(int $level): int {
        if ($level <= 1) {
            return 0;
        }
        return (int) ($this->base * $level * ($level - 1) / 2);
    }

    /**
     * Get the XP needed to progress from the current level to the next.
     *
     * @param int $level Current level.
     * @return int XP required for next level.
     */
    public function get_xp_for_next_level(int $level): int {
        if ($level >= $this->maxlevel) {
            return 0; // Already at max level.
        }
        return $this->get_level_threshold($level + 1) - $this->get_level_threshold($level);
    }

    /**
     * Get progress percentage within the current level.
     *
     * @param int $points Total XP points.
     * @return float Progress percentage (0.0 to 100.0).
     */
    public function get_level_progress(int $points): float {
        $level = $this->calculate_level($points);

        if ($level >= $this->maxlevel) {
            return 100.0;
        }

        $currentthreshold = $this->get_level_threshold($level);
        $nextthreshold = $this->get_level_threshold($level + 1);
        $range = $nextthreshold - $currentthreshold;

        if ($range <= 0) {
            return 100.0;
        }

        return round(($points - $currentthreshold) / $range * 100, 1);
    }

    /**
     * Get the maximum level.
     *
     * @return int
     */
    public function get_max_level(): int {
        return $this->maxlevel;
    }
}
