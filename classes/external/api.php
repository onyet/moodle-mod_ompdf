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

namespace mod_ompdf\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * External API facade for OMPDF activity actions.
 *
 * @package    mod_ompdf
 * @category   external
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends external_api {
    /**
     * Defines the external API parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(PARAM_ALPHANUMEXT, 'Action name.'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID.'),
            'page' => new external_value(PARAM_INT, 'PDF page.', VALUE_DEFAULT, 0),
            'total' => new external_value(PARAM_INT, 'Total PDF pages.', VALUE_DEFAULT, 0),
            'duration' => new external_value(PARAM_INT, 'Reading duration in seconds.', VALUE_DEFAULT, 0),
            'content' => new external_value(PARAM_TEXT, 'Annotation content.', VALUE_DEFAULT, ''),
            'color' => new external_value(PARAM_ALPHA, 'Annotation colour.', VALUE_DEFAULT, 'yellow'),
            'type' => new external_value(PARAM_ALPHA, 'Annotation type.', VALUE_DEFAULT, 'student'),
            'id' => new external_value(PARAM_INT, 'Annotation ID.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Executes an OMPDF API action.
     *
     * @param string[] $params API parameters.
     * @return string JSON encoded response.
     */
    public static function execute(array $params): string {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), $params);
        $cm = get_coursemodule_from_id('ompdf', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_login($course, false, $cm);
        require_capability('mod/ompdf:view', $context);

        switch ($params['action']) {
            case 'save_position':
                set_user_preference('mod_ompdf_lastpage_' . $cm->id, $params['page']);
                return json_encode(['status' => 'ok', 'page' => $params['page']]);

            case 'get_position':
                return json_encode([
                    'status' => 'ok',
                    'page' => (int)get_user_preferences('mod_ompdf_lastpage_' . $cm->id, 1),
                ]);

            case 'track_progress':
                set_user_preference('mod_ompdf_lastpage_' . $cm->id, $params['page']);
                self::record_progress($cm->instance, $params['page'], $params['duration']);
                $completed = $params['total'] > 0 && $params['page'] >= $params['total'];
                if ($completed) {
                    $completion = new \completion_info($course);
                    if ($completion->is_enabled($cm)) {
                        $completion->update_state($cm, COMPLETION_COMPLETE);
                    }
                }
                $percentage = $params['total'] > 0 ? round(($params['page'] / $params['total']) * 100) : 0;
                return json_encode(['status' => 'ok', 'progress' => $percentage, 'completed' => $completed]);

            case 'save_annotation':
                $record = (object)[
                    'ompdfid' => $cm->instance,
                    'userid' => $USER->id,
                    'page' => $params['page'],
                    'content' => $params['content'],
                    'color' => $params['color'],
                    'type' => $params['type'] === 'teacher'
                        && has_capability('moodle/course:manageactivities', $context) ? 'teacher' : 'student',
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $record->id = $DB->insert_record('ompdf_annotations', $record);
                $record->author = fullname($USER);
                return json_encode(['status' => 'ok', 'annotation' => $record]);

            case 'get_annotations':
                $conditions = ['ompdfid' => $cm->instance];
                if ($params['page'] > 0) {
                    $conditions['page'] = $params['page'];
                }
                $records = $DB->get_records('ompdf_annotations', $conditions, 'timecreated ASC');
                $userids = [];
                foreach ($records as $record) {
                    $userids[$record->userid] = (int)$record->userid;
                }
                $users = $userids ? $DB->get_records_list(
                    'user',
                    'id',
                    array_values($userids),
                    '',
                    'id, firstname, lastname'
                ) : [];
                $records = array_filter($records, function ($record) use ($users, $USER) {
                    if ($record->type !== 'teacher' && $record->userid != $USER->id) {
                        return false;
                    }
                    $user = $users[$record->userid] ?? null;
                    $record->author = $user ? fullname($user) : 'User';
                    $record->is_owner = $record->userid == $USER->id;
                    return true;
                });
                return json_encode(['status' => 'ok', 'annotations' => array_values($records)]);

            case 'delete_annotation':
                $record = $DB->get_record('ompdf_annotations', [
                    'id' => $params['id'],
                    'ompdfid' => $cm->instance,
                ], '*', MUST_EXIST);
                if (
                    $record->userid != $USER->id
                        && !has_capability('moodle/course:manageactivities', $context)
                ) {
                    throw new \required_capability_exception($context, 'mod/ompdf:view', 'nopermissions', 'delete annotation');
                }
                $DB->delete_records('ompdf_annotations', ['id' => $record->id]);
                return json_encode(['status' => 'ok']);
        }

        throw new \invalid_parameter_exception('Unknown OMPDF action.');
    }

    /**
     * Defines the external API return type.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'JSON encoded API response.');
    }

    /**
     * Records reading progress analytics.
     *
     * @param int $instanceid OMPDF instance ID.
     * @param int $page Current page.
     * @param int $duration Duration in seconds.
     */
    private static function record_progress(int $instanceid, int $page, int $duration): void {
        global $DB, $USER;
        if ($duration <= 0 || $duration > 3600 || !$DB->get_manager()->table_exists('ompdf_analytics')) {
            return;
        }
        $record = $DB->get_record('ompdf_analytics', [
            'ompdfid' => $instanceid,
            'userid' => $USER->id,
            'page' => $page,
        ]);
        if ($record) {
            $record->duration += $duration;
            $record->timemodified = time();
            $DB->update_record('ompdf_analytics', $record);
            return;
        }
        $record = (object)[
            'ompdfid' => $instanceid,
            'userid' => $USER->id,
            'page' => $page,
            'duration' => $duration,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $DB->insert_record('ompdf_analytics', $record);
    }
}
