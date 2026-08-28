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
 * Full leaderboard page for local_xp.
 *
 * Displays a paginated leaderboard with user XP rankings.
 * Supports both course-specific and system-wide views.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 20, PARAM_INT);

// Require login.
if ($courseid > 0) {
    $course = get_course($courseid);
    require_login($course);
    $context = context_course::instance($courseid);
} else {
    require_login();
    $context = context_system::instance();
}

// Check capability.
require_capability('local/xp:viewleaderboard', $context);

// Page setup.
$PAGE->set_context($context);

if ($courseid > 0) {
    $PAGE->set_course($course);
    $PAGE->set_pagelayout('incourse');
    $title = get_string('leaderboard_course', 'local_xp', $course->fullname);
} else {
    $PAGE->set_pagelayout('standard');
    $title = get_string('leaderboard_system', 'local_xp');
}

$pageurl = new moodle_url('/local/xp/leaderboard.php', ['courseid' => $courseid, 'page' => $page]);
$PAGE->set_url($pageurl);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add plugin CSS.
$PAGE->requires->css('/local/xp/styles.css');

// Get leaderboard data.
$leaderboard = new \local_xp\leaderboard($perpage);
$result = $leaderboard->get_leaderboard($courseid, $page, $perpage);

// Get current user's data.
$manager = new \local_xp\manager();
$userrank = $leaderboard->get_user_rank($USER->id, $courseid);

if ($courseid > 0) {
    $userxp = $manager->get_user_xp($USER->id, $courseid);
} else {
    // For system-wide, build an aggregate object.
    $totalpoints = $manager->get_user_total_xp($USER->id);
    if ($totalpoints > 0) {
        $levelcalc = $manager->get_level_calculator();
        $userxp = (object) [
            'points' => $totalpoints,
            'level' => $levelcalc->calculate_level($totalpoints),
        ];
    } else {
        $userxp = false;
    }
}

// Prepare renderable.
$renderable = new \local_xp\output\leaderboard_page(
    $result['entries'],
    $result['totalcount'],
    $userrank,
    $userxp,
    $courseid,
    $page,
    $perpage,
    $USER->id
);

// Render page.
$renderer = $PAGE->get_renderer('local_xp');
echo $OUTPUT->header();
echo $renderer->render_leaderboard_page($renderable);
echo $OUTPUT->footer();
