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
 * Web service definitions for local_xp.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'local_xp_get_user_xp' => [
        'classname'     => 'local_xp\external\get_user_xp',
        'description'   => 'Get XP, level, and progress for a user.',
        'type'          => 'read',
        'capabilities'  => 'local/xp:viewownxp',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'local_xp_get_leaderboard' => [
        'classname'     => 'local_xp\external\get_leaderboard',
        'description'   => 'Get the leaderboard for a course or system-wide.',
        'type'          => 'read',
        'capabilities'  => 'local/xp:viewleaderboard',
        'ajax'          => true,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'local_xp_award_manual_xp' => [
        'classname'     => 'local_xp\external\award_manual_xp',
        'description'   => 'Manually award XP to a user.',
        'type'          => 'write',
        'capabilities'  => 'local/xp:awardxp',
        'ajax'          => true,
    ],
];
