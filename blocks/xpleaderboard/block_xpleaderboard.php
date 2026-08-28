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
 * XP Leaderboard block.
 *
 * Displays a compact Top 10 leaderboard widget that can be added to
 * any course page or the dashboard. Shows current user's rank and
 * links to the full leaderboard page.
 *
 * @package    block_xpleaderboard
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_xpleaderboard extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_xpleaderboard');
    }

    /**
     * Define where this block can be used.
     *
     * @return array Format => bool.
     */
    public function applicable_formats() {
        return [
            'course-view' => true,
            'site-index'  => true,
            'my'          => true, // Dashboard.
        ];
    }

    /**
     * Only allow one instance per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Build the block content.
     *
     * @return stdClass Block content with text and footer.
     */
    public function get_content() {
        global $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if XP system is enabled.
        if (!get_config('local_xp', 'enabled')) {
            return $this->content;
        }

        // Determine course context.
        $course = $this->page->course;
        $courseid = ($course->id != SITEID) ? $course->id : 0;

        if ($courseid > 0) {
            $context = context_course::instance($courseid);
        } else {
            $context = context_system::instance();
        }

        // Check capability.
        if (!has_capability('local/xp:viewleaderboard', $context)) {
            return $this->content;
        }

        // Load plugin CSS.
        $PAGE->requires->css('/local/xp/styles.css');

        // Get leaderboard data.
        $leaderboard = new \local_xp\leaderboard();
        $topentries = $leaderboard->get_top($courseid, 10);

        // Get current user data.
        $manager = new \local_xp\manager();
        $levelcalc = $manager->get_level_calculator();
        $userrank = $leaderboard->get_user_rank($USER->id, $courseid);

        // Build user summary.
        $usersummary = null;
        if ($courseid > 0) {
            $userxp = $manager->get_user_xp($USER->id, $courseid);
        } else {
            $totalpoints = $manager->get_user_total_xp($USER->id);
            if ($totalpoints > 0) {
                $userxp = (object) [
                    'points' => $totalpoints,
                    'level' => $levelcalc->calculate_level($totalpoints),
                ];
            } else {
                $userxp = false;
            }
        }

        if ($userxp) {
            $usersummary = [
                'rank' => $userrank,
                'points' => number_format((int) $userxp->points),
                'level' => (int) $userxp->level,
                'progress' => $levelcalc->get_level_progress((int) $userxp->points),
            ];
        }

        // Format entries for the template.
        $entries = [];
        foreach ($topentries as $entry) {
            $userpicture = new user_picture((object) [
                'id' => $entry->userid,
                'picture' => $entry->picture ?? 0,
                'firstname' => $entry->firstname,
                'lastname' => $entry->lastname,
                'email' => $entry->email ?? '',
                'imagealt' => $entry->imagealt ?? '',
                'firstnamephonetic' => $entry->firstnamephonetic ?? '',
                'lastnamephonetic' => $entry->lastnamephonetic ?? '',
                'middlename' => $entry->middlename ?? '',
                'alternatename' => $entry->alternatename ?? '',
            ]);
            $userpicture->size = 24;

            $entries[] = [
                'rank' => $entry->rank,
                'userid' => $entry->userid,
                'fullname' => fullname($entry),
                'userpictureurl' => $userpicture->get_url($PAGE)->out(false),
                'points' => number_format((int) $entry->points),
                'iscurrentuser' => ($entry->userid == $USER->id),
                'istop1' => ($entry->rank == 1),
                'istop2' => ($entry->rank == 2),
                'istop3' => ($entry->rank == 3),
            ];
        }

        // Render using local_xp's renderer and template.
        $renderer = $PAGE->get_renderer('local_xp');
        $this->content->text = $renderer->render_block_leaderboard([
            'hasentries' => !empty($entries),
            'entries' => $entries,
            'hasusersummary' => ($usersummary !== null),
            'usersummary' => $usersummary,
            'leaderboardurl' => (new moodle_url('/local/xp/leaderboard.php', [
                'courseid' => $courseid,
            ]))->out(false),
        ]);

        return $this->content;
    }

    /**
     * This block should not be added if the XP system is disabled.
     *
     * @param moodle_page $page
     * @return bool
     */
    public function can_block_be_added(moodle_page $page): bool {
        return (bool) get_config('local_xp', 'enabled');
    }
}
