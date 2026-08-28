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
 * User XP profile page.
 *
 * Displays a user's XP summary, level progress, and history log.
 * Users can view their own XP; teachers/managers can view any user's XP.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

// Determine context.
if ($courseid > 0) {
    $course = get_course($courseid);
    $context = context_course::instance($courseid);
    require_login($course);
} else {
    $context = context_system::instance();
}

// Permission check.
$viewingself = ($userid == $USER->id);
if (!$viewingself) {
    require_capability('local/xp:viewallxp', $context);
} else {
    require_capability('local/xp:viewownxp', $context);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/xp/user_xp.php', ['userid' => $userid, 'courseid' => $courseid]));

$targetuser = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$PAGE->set_title(get_string('user_xp_title', 'local_xp', fullname($targetuser)));
$PAGE->set_heading(get_string('user_xp_title', 'local_xp', fullname($targetuser)));

if ($courseid > 0) {
    $PAGE->set_pagelayout('incourse');
} else {
    $PAGE->set_pagelayout('standard');
}

$manager = new \local_xp\manager();
$levelcalc = $manager->get_level_calculator();

// Get user's XP data.
if ($courseid > 0) {
    $xprecord = $manager->get_user_xp($userid, $courseid);
} else {
    // Get aggregate across all courses.
    $xprecord = null;
    $totalxp = $manager->get_user_total_xp($userid);
}

$points = $xprecord ? (int) $xprecord->points : ($totalxp ?? 0);
$level = $levelcalc->calculate_level($points);
$progress = $levelcalc->get_level_progress($points);
$currentthreshold = $levelcalc->get_level_threshold($level);
$nextthreshold = $levelcalc->get_level_threshold($level + 1);
$xptonext = $nextthreshold - $points;
$maxlevel = $levelcalc->get_max_level();

// Get XP history.
$logs = $manager->get_user_log($userid, $courseid, $page * $perpage, $perpage);
$totallogcount = $DB->count_records_select(
    'local_xp_log',
    'userid = :userid' . ($courseid > 0 ? ' AND courseid = :courseid' : ''),
    $courseid > 0 ? ['userid' => $userid, 'courseid' => $courseid] : ['userid' => $userid]
);

// Get per-course breakdown.
$coursebreakdown = [];
if ($courseid == 0) {
    $sql = "SELECT xp.courseid, c.fullname, xp.points, xp.level
              FROM {local_xp_points} xp
              JOIN {course} c ON c.id = xp.courseid
             WHERE xp.userid = :userid AND xp.courseid > 0
          ORDER BY xp.points DESC";
    $coursebreakdown = $DB->get_records_sql($sql, ['userid' => $userid]);
}

echo $OUTPUT->header();

// User profile card.
echo html_writer::start_tag('div', ['class' => 'local-xp-user-profile']);

// XP Summary Card.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-body']);

// User avatar + name.
echo html_writer::start_tag('div', ['class' => 'd-flex align-items-center mb-3']);
echo $OUTPUT->user_picture($targetuser, ['size' => 64, 'class' => 'mr-3 me-3']);
echo html_writer::start_tag('div');
echo html_writer::tag('h3', fullname($targetuser), ['class' => 'mb-0']);
if ($courseid > 0) {
    echo html_writer::tag('small', $course->fullname, ['class' => 'text-muted']);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Stats row.
echo html_writer::start_tag('div', ['class' => 'row text-center']);

// Total XP.
echo html_writer::start_tag('div', ['class' => 'col-md-3 col-6 mb-3']);
echo html_writer::start_tag('div', ['class' => 'p-3 bg-light rounded']);
echo html_writer::tag('div', '⭐', ['class' => 'display-4']);
echo html_writer::tag('h4', number_format($points), ['class' => 'text-primary mb-0']);
echo html_writer::tag('small', get_string('totalxp', 'local_xp'), ['class' => 'text-muted']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Level.
echo html_writer::start_tag('div', ['class' => 'col-md-3 col-6 mb-3']);
echo html_writer::start_tag('div', ['class' => 'p-3 bg-light rounded']);
echo html_writer::tag('div', '🏅', ['class' => 'display-4']);
echo html_writer::tag('h4', $level, ['class' => 'text-success mb-0']);
echo html_writer::tag('small', get_string('currentlevel', 'local_xp'), ['class' => 'text-muted']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// XP to next level.
echo html_writer::start_tag('div', ['class' => 'col-md-3 col-6 mb-3']);
echo html_writer::start_tag('div', ['class' => 'p-3 bg-light rounded']);
echo html_writer::tag('div', '🎯', ['class' => 'display-4']);
if ($level >= $maxlevel) {
    echo html_writer::tag('h4', '🏆', ['class' => 'mb-0']);
    echo html_writer::tag('small', get_string('maxlevelreached', 'local_xp'), ['class' => 'text-muted']);
} else {
    echo html_writer::tag('h4', number_format($xptonext), ['class' => 'text-warning mb-0']);
    echo html_writer::tag('small', get_string('xptonextlevel', 'local_xp', ''), ['class' => 'text-muted']);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Total logs.
echo html_writer::start_tag('div', ['class' => 'col-md-3 col-6 mb-3']);
echo html_writer::start_tag('div', ['class' => 'p-3 bg-light rounded']);
echo html_writer::tag('div', '📊', ['class' => 'display-4']);
echo html_writer::tag('h4', $totallogcount, ['class' => 'text-info mb-0']);
echo html_writer::tag('small', get_string('total_events', 'local_xp'), ['class' => 'text-muted']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');  // End stats row.

// Progress bar.
echo html_writer::start_tag('div', ['class' => 'mt-3']);
echo html_writer::tag('div',
    get_string('level_progress_label', 'local_xp', (object) [
        'current' => $level,
        'next' => min($level + 1, $maxlevel),
        'percent' => round($progress),
    ]),
    ['class' => 'mb-1 font-weight-bold fw-bold']
);
echo html_writer::start_tag('div', ['class' => 'progress', 'style' => 'height: 24px;']);
echo html_writer::tag('div', round($progress) . '%', [
    'class' => 'progress-bar bg-success progress-bar-striped progress-bar-animated',
    'role' => 'progressbar',
    'style' => 'width: ' . round($progress) . '%;',
    'aria-valuenow' => round($progress),
    'aria-valuemin' => '0',
    'aria-valuemax' => '100',
]);
echo html_writer::end_tag('div');
if ($level < $maxlevel) {
    echo html_writer::tag('small',
        number_format($points - $currentthreshold) . ' / ' . number_format($nextthreshold - $currentthreshold) . ' XP',
        ['class' => 'text-muted']
    );
}
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');  // End card-body.
echo html_writer::end_tag('div');  // End card.

// Course breakdown (only for system-wide view).
if ($courseid == 0 && !empty($coursebreakdown)) {
    echo html_writer::start_tag('div', ['class' => 'card mb-4']);
    echo html_writer::start_tag('div', ['class' => 'card-header']);
    echo html_writer::tag('h4', get_string('course_breakdown', 'local_xp'), ['class' => 'mb-0']);
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div', ['class' => 'card-body']);

    $table = new html_table();
    $table->head = [
        get_string('course'),
        get_string('points', 'local_xp'),
        get_string('level', 'local_xp'),
        '',
    ];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($coursebreakdown as $row) {
        $viewurl = new moodle_url('/local/xp/user_xp.php', [
            'userid' => $userid, 'courseid' => $row->courseid,
        ]);
        $table->data[] = [
            $row->fullname,
            html_writer::tag('strong', number_format($row->points) . ' XP'),
            get_string('level', 'local_xp') . ' ' . $row->level,
            html_writer::link($viewurl, get_string('view'), ['class' => 'btn btn-sm btn-outline-primary']),
        ];
    }

    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

// XP History Log.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h4', get_string('xplog', 'local_xp'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

if (!empty($logs)) {
    $table = new html_table();
    $table->head = [
        get_string('date'),
        get_string('course'),
        get_string('reason_label', 'local_xp'),
        get_string('points', 'local_xp'),
    ];
    $table->attributes['class'] = 'table table-striped generaltable';

    foreach ($logs as $log) {
        // Get course name.
        $coursename = '-';
        if ($log->courseid > 0) {
            $logcourse = $DB->get_record('course', ['id' => $log->courseid], 'fullname');
            $coursename = $logcourse ? $logcourse->fullname : get_string('unknowncourse', 'local_xp');
        }

        // Get localized reason.
        $reasonkey = 'reason_' . $log->reason;
        $reason = get_string_manager()->string_exists($reasonkey, 'local_xp')
            ? get_string($reasonkey, 'local_xp')
            : $log->reason;

        $table->data[] = [
            userdate($log->timecreated, get_string('strftimedatetime', 'langconfig')),
            $coursename,
            $reason,
            html_writer::tag('span', '+' . $log->points . ' XP', [
                'class' => 'badge badge-success bg-success text-white',
            ]),
        ];
    }

    echo html_writer::table($table);

    // Pagination.
    echo $OUTPUT->paging_bar($totallogcount, $page, $perpage, $PAGE->url);
} else {
    echo $OUTPUT->notification(get_string('noxpyet', 'local_xp'), 'info');
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Navigation links.
echo html_writer::start_tag('div', ['class' => 'mt-3']);
$leaderboardurl = new moodle_url('/local/xp/leaderboard.php', $courseid > 0 ? ['courseid' => $courseid] : []);
echo html_writer::link($leaderboardurl, get_string('viewfullleaderboard', 'local_xp'), [
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');  // End local-xp-user-profile.

echo $OUTPUT->footer();
