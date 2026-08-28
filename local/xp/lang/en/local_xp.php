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
 * English language strings for local_xp.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Experience Points (XP)';

// ===== Privacy =====
$string['privacy:metadata'] = 'The XP plugin stores user experience points and level data.';
$string['privacy:metadata:local_xp_points'] = 'Stores the accumulated XP and level for each user.';
$string['privacy:metadata:local_xp_points:userid'] = 'The ID of the user.';
$string['privacy:metadata:local_xp_points:courseid'] = 'The course the XP belongs to.';
$string['privacy:metadata:local_xp_points:points'] = 'The total accumulated XP points.';
$string['privacy:metadata:local_xp_points:level'] = 'The user\'s current level.';
$string['privacy:metadata:local_xp_points:timecreated'] = 'When the record was first created.';
$string['privacy:metadata:local_xp_points:timemodified'] = 'When the record was last modified.';
$string['privacy:metadata:local_xp_log'] = 'Logs every individual XP award event for audit.';
$string['privacy:metadata:local_xp_log:userid'] = 'The ID of the user who received XP.';
$string['privacy:metadata:local_xp_log:courseid'] = 'The course in which XP was earned.';
$string['privacy:metadata:local_xp_log:points'] = 'The amount of XP awarded.';
$string['privacy:metadata:local_xp_log:reason'] = 'The reason for the XP award.';
$string['privacy:metadata:local_xp_log:eventname'] = 'The Moodle event that triggered the award.';
$string['privacy:metadata:local_xp_log:timecreated'] = 'When the XP was awarded.';

// ===== Capabilities =====
$string['xp:earnxp'] = 'Earn experience points';
$string['xp:viewownxp'] = 'View own experience points';
$string['xp:viewleaderboard'] = 'View the leaderboard';
$string['xp:viewallxp'] = 'View all users\' experience points';
$string['xp:managexprules'] = 'Manage XP rules';
$string['xp:awardxp'] = 'Manually award XP to users';

// ===== General =====
$string['xppoints'] = 'XP Points';
$string['level'] = 'Level';
$string['leaderboard'] = 'Leaderboard';
$string['xplog'] = 'XP History';
$string['totalxp'] = 'Total XP';
$string['currentlevel'] = 'Current Level';
$string['progress'] = 'Progress';
$string['rank'] = 'Rank';
$string['user'] = 'User';
$string['points'] = 'Points';
$string['noxpyet'] = 'No experience points earned yet.';
$string['noleaderboarddata'] = 'No leaderboard data available yet.';

// ===== XP Reasons =====
$string['reason_course_completed'] = 'Course completed';
$string['reason_module_completed'] = 'Activity completed';
$string['reason_grade_achieved'] = 'Grade achieved';
$string['reason_badge_earned'] = 'Badge earned';
$string['reason_manual'] = 'Manually awarded';
$string['reason_label'] = 'Reason';

// ===== Leaderboard Page =====
$string['leaderboard_title'] = 'Leaderboard';
$string['leaderboard_course'] = 'Course Leaderboard: {$a}';
$string['leaderboard_system'] = 'System Leaderboard';
$string['viewfullleaderboard'] = 'View full leaderboard';
$string['yourposition'] = 'Your position';
$string['top10'] = 'Top 10';
$string['topn'] = 'Top {$a}';
$string['of'] = 'of';
$string['xptonextlevel'] = '{$a} XP to next level';
$string['maxlevelreached'] = 'Max level reached!';
$string['levelup'] = 'Level Up!';

// ===== Settings - General =====
$string['settings_heading'] = 'XP System Settings';
$string['settings_general'] = 'General Settings';
$string['settings_general_desc'] = 'Configure the overall behavior of the XP system.';
$string['settings_enabled'] = 'Enable XP system';
$string['settings_enabled_desc'] = 'When enabled, users will earn experience points for completing activities and courses.';

// ===== Settings - XP Awards =====
$string['settings_xp_heading'] = 'XP Award Settings';
$string['settings_xp_heading_desc'] = 'Configure default XP values for different activities.';
$string['settings_course_points'] = 'Course completion XP';
$string['settings_course_points_desc'] = 'Default XP awarded when a user completes a course.';
$string['settings_module_points'] = 'Activity completion XP';
$string['settings_module_points_desc'] = 'Default XP awarded when a user completes an activity module.';
$string['settings_grade_bonus'] = 'Enable grade bonus';
$string['settings_grade_bonus_desc'] = 'Award bonus XP when a user achieves a grade above the threshold.';
$string['settings_grade_threshold'] = 'Grade bonus threshold (%)';
$string['settings_grade_threshold_desc'] = 'Minimum grade percentage required to earn grade bonus XP.';
$string['settings_grade_points'] = 'Grade bonus XP';
$string['settings_grade_points_desc'] = 'XP awarded for achieving a grade above the threshold.';

// ===== Settings - Level =====
$string['settings_level_heading'] = 'Level System';
$string['settings_level_heading_desc'] = 'Configure the leveling algorithm and limits.';
$string['settings_level_algorithm'] = 'Level algorithm';
$string['settings_level_algorithm_desc'] = 'How XP thresholds increase between levels.';
$string['algorithm_quadratic'] = 'Quadratic (progressive difficulty)';
$string['algorithm_linear'] = 'Linear (constant difficulty)';
$string['settings_max_level'] = 'Maximum level';
$string['settings_max_level_desc'] = 'The highest level a user can achieve.';
$string['settings_level_base'] = 'Base XP per level';
$string['settings_level_base_desc'] = 'The base XP increment used in level calculations.';

// ===== Settings - Leaderboard =====
$string['settings_leaderboard_heading'] = 'Leaderboard Settings';
$string['settings_leaderboard_heading_desc'] = 'Configure leaderboard display options.';
$string['settings_leaderboard_limit'] = 'Leaderboard display limit';
$string['settings_leaderboard_limit_desc'] = 'Maximum number of users shown on the leaderboard page.';
$string['settings_anonymize'] = 'Anonymize leaderboard';
$string['settings_anonymize_desc'] = 'Hide real names on the leaderboard and show only usernames or initials.';

// ===== Rule Management =====
$string['manage_rules'] = 'Manage XP Rules';
$string['manage_rules_desc'] = 'Create, edit, and manage the rules that determine how XP is awarded for different activities.';
$string['manage_rules_link'] = 'Open XP Rule Manager';
$string['add_rule'] = 'Add New Rule';
$string['edit_rule'] = 'Edit Rule';
$string['save_rule'] = 'Save Rule';
$string['event'] = 'Event';
$string['conditions_label'] = 'Conditions';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['no_rules'] = 'No XP rules defined yet. Add your first rule below.';
$string['rule_created'] = 'XP rule created successfully.';
$string['rule_updated'] = 'XP rule updated successfully.';
$string['rule_deleted'] = 'XP rule deleted.';
$string['rule_enabled'] = 'Rule has been enabled.';
$string['rule_disabled'] = 'Rule has been disabled.';
$string['rules_reset'] = 'All rules have been reset to defaults.';
$string['reset_defaults'] = 'Reset to defaults';
$string['confirm_delete_rule'] = 'Are you sure you want to delete this rule?';
$string['confirm_reset'] = 'This will delete all current rules and restore defaults. Are you sure?';
$string['min_grade_condition'] = 'Min grade ≥ {$a}%';
$string['min_grade_label'] = 'Minimum grade (%)';
$string['min_grade_help'] = 'Only award XP when the grade percentage equals or exceeds this value.';

// ===== User XP Page =====
$string['user_xp_title'] = 'XP Profile: {$a}';
$string['total_events'] = 'Total Events';
$string['level_progress_label'] = 'Level {$a->current} → Level {$a->next} ({$a->percent}%)';
$string['course_breakdown'] = 'XP by Course';
$string['unknowncourse'] = 'Unknown course';
$string['view'] = 'View';

// ===== Block =====
$string['xpleaderboard'] = 'XP Leaderboard';

// ===== Events =====
$string['event_xp_awarded'] = 'XP awarded';

// ===== Filters =====
$string['filter_allcourses'] = 'All courses';
$string['filter_alltime'] = 'All time';
$string['filter_thisweek'] = 'This week';
$string['filter_thismonth'] = 'This month';

// ===== Web Services =====
$string['invalid_points'] = 'Points must be a positive number.';
$string['xp_awarded_success'] = '{$a} XP awarded successfully.';
$string['xp_award_failed'] = 'Failed to award XP. The XP may have already been awarded for this event.';

// ===== Scheduled Tasks =====
$string['task_recalculate_levels'] = 'Recalculate user levels';
