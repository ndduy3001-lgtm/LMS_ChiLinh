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

namespace local_xp\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for local_xp.
 *
 * Implements GDPR compliance by declaring stored user data,
 * exporting user data on request, and deleting user data on request.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the types of data stored by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_xp_points', [
            'userid' => 'privacy:metadata:local_xp_points:userid',
            'courseid' => 'privacy:metadata:local_xp_points:courseid',
            'points' => 'privacy:metadata:local_xp_points:points',
            'level' => 'privacy:metadata:local_xp_points:level',
            'timecreated' => 'privacy:metadata:local_xp_points:timecreated',
            'timemodified' => 'privacy:metadata:local_xp_points:timemodified',
        ], 'privacy:metadata:local_xp_points');

        $collection->add_database_table('local_xp_log', [
            'userid' => 'privacy:metadata:local_xp_log:userid',
            'courseid' => 'privacy:metadata:local_xp_log:courseid',
            'points' => 'privacy:metadata:local_xp_log:points',
            'reason' => 'privacy:metadata:local_xp_log:reason',
            'eventname' => 'privacy:metadata:local_xp_log:eventname',
            'timecreated' => 'privacy:metadata:local_xp_log:timecreated',
        ], 'privacy:metadata:local_xp_log');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // XP points in course contexts.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {local_xp_points} xp
                  JOIN {context} ctx ON ctx.instanceid = xp.courseid AND ctx.contextlevel = :contextlevel
                 WHERE xp.userid = :userid AND xp.courseid > 0";
        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'contextlevel' => CONTEXT_COURSE,
        ]);

        // System-wide XP.
        $sql = "SELECT ctx.id
                  FROM {local_xp_points} xp
                  JOIN {context} ctx ON ctx.contextlevel = :systemlevel AND ctx.instanceid = 0
                 WHERE xp.userid = :userid AND xp.courseid = 0";
        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'systemlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_COURSE) {
            $sql = "SELECT DISTINCT userid FROM {local_xp_points} WHERE courseid = :courseid";
            $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);

            $sql = "SELECT DISTINCT userid FROM {local_xp_log} WHERE courseid = :courseid";
            $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
        } else if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = "SELECT DISTINCT userid FROM {local_xp_points} WHERE courseid = 0";
            $userlist->add_from_sql('userid', $sql, []);

            $sql = "SELECT DISTINCT userid FROM {local_xp_log} WHERE courseid = 0";
            $userlist->add_from_sql('userid', $sql, []);
        }
    }

    /**
     * Export all user data for the specified approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $courseid = ($context->contextlevel == CONTEXT_COURSE) ? $context->instanceid : 0;

            // Export XP points.
            $records = $DB->get_records('local_xp_points', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);

            if ($records) {
                $data = [];
                foreach ($records as $record) {
                    $data[] = (object) [
                        'points' => $record->points,
                        'level' => $record->level,
                        'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_xp'), get_string('xppoints', 'local_xp')],
                    (object) ['points' => $data]
                );
            }

            // Export XP log.
            $params = ['userid' => $userid, 'courseid' => $courseid];
            $logs = $DB->get_records('local_xp_log', $params, 'timecreated DESC');

            if ($logs) {
                $data = [];
                foreach ($logs as $log) {
                    $data[] = (object) [
                        'points' => $log->points,
                        'reason' => $log->reason,
                        'eventname' => $log->eventname,
                        'timecreated' => \core_privacy\local\request\transform::datetime($log->timecreated),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_xp'), get_string('xplog', 'local_xp')],
                    (object) ['logs' => $data]
                );
            }
        }
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->delete_records('local_xp_points', ['courseid' => $context->instanceid]);
            $DB->delete_records('local_xp_log', ['courseid' => $context->instanceid]);
        } else if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_xp_points', ['courseid' => 0]);
            $DB->delete_records('local_xp_log', ['courseid' => 0]);
        }
    }

    /**
     * Delete all personal data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $courseid = ($context->contextlevel == CONTEXT_COURSE) ? $context->instanceid : 0;

            $DB->delete_records('local_xp_points', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);
            $DB->delete_records('local_xp_log', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);
        }
    }

    /**
     * Delete multiple users' data within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        $courseid = ($context->contextlevel == CONTEXT_COURSE) ? $context->instanceid : 0;

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $courseid;

        $DB->delete_records_select('local_xp_points',
            "userid $insql AND courseid = :courseid", $params);
        $DB->delete_records_select('local_xp_log',
            "userid $insql AND courseid = :courseid", $params);
    }
}
