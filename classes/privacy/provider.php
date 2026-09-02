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
 * Privacy provider for mod_ompdf.
 *
 * @package    mod_ompdf
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ompdf\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Describes and manages personal data stored by OMPDF.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Adds OMPDF personal data metadata to the collection.
     *
     * @param collection $collection Metadata collection.
     * @return collection Updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('ompdf_analytics', [
            'userid' => 'privacy:metadata:ompdf_analytics:userid',
            'ompdfid' => 'privacy:metadata:ompdf_analytics:ompdfid',
            'page' => 'privacy:metadata:ompdf_analytics:page',
            'duration' => 'privacy:metadata:ompdf_analytics:duration',
            'timecreated' => 'privacy:metadata:ompdf_analytics:timecreated',
            'timemodified' => 'privacy:metadata:ompdf_analytics:timemodified',
        ], 'privacy:metadata:ompdf_analytics');
        $collection->add_database_table('ompdf_annotations', [
            'userid' => 'privacy:metadata:ompdf_annotations:userid',
            'ompdfid' => 'privacy:metadata:ompdf_annotations:ompdfid',
            'page' => 'privacy:metadata:ompdf_annotations:page',
            'content' => 'privacy:metadata:ompdf_annotations:content',
            'color' => 'privacy:metadata:ompdf_annotations:color',
            'type' => 'privacy:metadata:ompdf_annotations:type',
            'timecreated' => 'privacy:metadata:ompdf_annotations:timecreated',
            'timemodified' => 'privacy:metadata:ompdf_annotations:timemodified',
        ], 'privacy:metadata:ompdf_annotations');
        $collection->add_user_preference(
            'mod_ompdf_lastpage_',
            'privacy:metadata:mod_ompdf_lastpage'
        );

        return $collection;
    }

    /**
     * Gets module contexts containing data for a user.
     *
     * @param int $userid User ID.
     * @return contextlist Context list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = ['userid' => $userid, 'contextlevel' => CONTEXT_MODULE, 'modname' => 'ompdf'];
        $joins = [
            ['ompdf_analytics', 'a', 'a.ompdfid = cm.instance AND a.userid = :userid'],
            ['ompdf_annotations', 'n', 'n.ompdfid = cm.instance AND n.userid = :userid'],
        ];

        foreach ($joins as [$table, $alias, $condition]) {
            $sql = "SELECT ctx.id
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                      JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                      JOIN {{$table}} {$alias} ON {$condition}";
            $contextlist->add_from_sql($sql, $params);
        }

        $sql = "SELECT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :prefmodname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :prefcontextlevel
                  JOIN {user_preferences} up ON up.name = :prefname
                 WHERE up.userid = :prefuserid
                   AND up.name LIKE :prefname";
        $contextlist->add_from_sql($sql, [
            'prefmodname' => 'ompdf',
            'prefcontextlevel' => CONTEXT_MODULE,
            'prefname' => 'mod_ompdf_lastpage_%',
            'prefuserid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Gets users with OMPDF data in a context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $params = ['contextid' => $context->id, 'modname' => 'ompdf'];
        $sql = "SELECT a.userid
                  FROM {ompdf_analytics} a
                  JOIN {course_modules} cm ON cm.instance = a.ompdfid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.id = :contextid
                 UNION
                SELECT n.userid
                  FROM {ompdf_annotations} n
                  JOIN {course_modules} cm ON cm.instance = n.ompdfid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Exports a user's OMPDF data.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('ompdf', $context->instanceid, 0, false, MUST_EXIST);
            $instanceid = $cm->instance;
            $analytics = $DB->get_records('ompdf_analytics', [
                'ompdfid' => $instanceid,
                'userid' => $userid,
            ]);
            $annotations = $DB->get_records('ompdf_annotations', [
                'ompdfid' => $instanceid,
                'userid' => $userid,
            ]);

            $data = [
                'analytics' => array_values($analytics),
                'annotations' => array_values($annotations),
            ];
            writer::with_context($context)->export_data([], (object)$data);
        }
    }

    /**
     * Deletes all OMPDF user data in a module context.
     *
     * @param \context $context Module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('ompdf', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $DB->delete_records('ompdf_analytics', ['ompdfid' => $cm->instance]);
        $DB->delete_records('ompdf_annotations', ['ompdfid' => $cm->instance]);
        $DB->delete_records('user_preferences', ['name' => 'mod_ompdf_lastpage_' . $cm->id]);
    }

    /**
     * Deletes a user's OMPDF data in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('ompdf', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('ompdf_analytics', ['ompdfid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('ompdf_annotations', ['ompdfid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('user_preferences', [
                'userid' => $userid,
                'name' => 'mod_ompdf_lastpage_' . $cm->id,
            ]);
        }
    }

    /**
     * Deletes multiple users' data in a module context.
     *
     * @param approved_userlist $userlist Approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('ompdf', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['ompdfid'] = $cm->instance;
        $DB->delete_records_select('ompdf_analytics', "ompdfid = :ompdfid AND userid {$insql}", $params);
        $DB->delete_records_select('ompdf_annotations', "ompdfid = :ompdfid AND userid {$insql}", $params);
        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('user_preferences', [
                'userid' => $userid,
                'name' => 'mod_ompdf_lastpage_' . $cm->id,
            ]);
        }
    }

    /**
     * Exports OMPDF user preferences.
     *
     * @param int $userid User ID.
     */
    public static function export_user_preferences(int $userid) {
        global $DB;

        $preferences = $DB->get_records_select(
            'user_preferences',
            'userid = :userid AND name LIKE :name',
            ['userid' => $userid, 'name' => 'mod_ompdf_lastpage_%']
        );
        foreach ($preferences as $preference) {
            writer::with_context(\context_system::instance())->export_user_preference(
                'mod_ompdf',
                $preference->name,
                $preference->value,
                get_string('privacy:metadata:mod_ompdf_lastpage', 'mod_ompdf')
            );
        }
    }
}
