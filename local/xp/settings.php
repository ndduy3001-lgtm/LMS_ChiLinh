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
 * Admin settings for local_xp.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_xp', get_string('pluginname', 'local_xp'));
    $ADMIN->add('localplugins', $settings);

    // === General Settings ===
    $settings->add(new admin_setting_heading(
        'local_xp/general_heading',
        get_string('settings_general', 'local_xp'),
        get_string('settings_general_desc', 'local_xp')
    ));

    // Enable/disable the XP system.
    $settings->add(new admin_setting_configcheckbox(
        'local_xp/enabled',
        get_string('settings_enabled', 'local_xp'),
        get_string('settings_enabled_desc', 'local_xp'),
        1 // Enabled by default.
    ));

    // === XP Award Settings ===
    $settings->add(new admin_setting_heading(
        'local_xp/xp_heading',
        get_string('settings_xp_heading', 'local_xp'),
        get_string('settings_xp_heading_desc', 'local_xp')
    ));

    // Default course completion XP.
    $settings->add(new admin_setting_configtext(
        'local_xp/course_completed_points',
        get_string('settings_course_points', 'local_xp'),
        get_string('settings_course_points_desc', 'local_xp'),
        200,
        PARAM_INT
    ));

    // Default activity completion XP.
    $settings->add(new admin_setting_configtext(
        'local_xp/module_completed_points',
        get_string('settings_module_points', 'local_xp'),
        get_string('settings_module_points_desc', 'local_xp'),
        20,
        PARAM_INT
    ));

    // Grade bonus enabled.
    $settings->add(new admin_setting_configcheckbox(
        'local_xp/grade_bonus_enabled',
        get_string('settings_grade_bonus', 'local_xp'),
        get_string('settings_grade_bonus_desc', 'local_xp'),
        1
    ));

    // Grade bonus threshold.
    $settings->add(new admin_setting_configtext(
        'local_xp/grade_bonus_threshold',
        get_string('settings_grade_threshold', 'local_xp'),
        get_string('settings_grade_threshold_desc', 'local_xp'),
        50,
        PARAM_INT
    ));

    // Grade bonus points.
    $settings->add(new admin_setting_configtext(
        'local_xp/grade_bonus_points',
        get_string('settings_grade_points', 'local_xp'),
        get_string('settings_grade_points_desc', 'local_xp'),
        30,
        PARAM_INT
    ));

    // === Level Settings ===
    $settings->add(new admin_setting_heading(
        'local_xp/level_heading',
        get_string('settings_level_heading', 'local_xp'),
        get_string('settings_level_heading_desc', 'local_xp')
    ));

    // Level algorithm.
    $settings->add(new admin_setting_configselect(
        'local_xp/level_algorithm',
        get_string('settings_level_algorithm', 'local_xp'),
        get_string('settings_level_algorithm_desc', 'local_xp'),
        'quadratic',
        [
            'quadratic' => get_string('algorithm_quadratic', 'local_xp'),
            'linear' => get_string('algorithm_linear', 'local_xp'),
        ]
    ));

    // Max level.
    $settings->add(new admin_setting_configtext(
        'local_xp/max_level',
        get_string('settings_max_level', 'local_xp'),
        get_string('settings_max_level_desc', 'local_xp'),
        50,
        PARAM_INT
    ));

    // Base XP per level.
    $settings->add(new admin_setting_configtext(
        'local_xp/level_base_xp',
        get_string('settings_level_base', 'local_xp'),
        get_string('settings_level_base_desc', 'local_xp'),
        100,
        PARAM_INT
    ));

    // === Leaderboard Settings ===
    $settings->add(new admin_setting_heading(
        'local_xp/leaderboard_heading',
        get_string('settings_leaderboard_heading', 'local_xp'),
        get_string('settings_leaderboard_heading_desc', 'local_xp')
    ));

    // Leaderboard display limit.
    $settings->add(new admin_setting_configtext(
        'local_xp/leaderboard_limit',
        get_string('settings_leaderboard_limit', 'local_xp'),
        get_string('settings_leaderboard_limit_desc', 'local_xp'),
        100,
        PARAM_INT
    ));

    // Anonymize leaderboard.
    $settings->add(new admin_setting_configcheckbox(
        'local_xp/anonymize_leaderboard',
        get_string('settings_anonymize', 'local_xp'),
        get_string('settings_anonymize_desc', 'local_xp'),
        0
    ));

    // === Manage Rules Link ===
    $settings->add(new admin_setting_heading(
        'local_xp/manage_heading',
        get_string('manage_rules', 'local_xp'),
        html_writer::link(
            new moodle_url('/local/xp/manage_rules.php'),
            get_string('manage_rules_link', 'local_xp'),
            ['class' => 'btn btn-primary']
        )
    ));
}
