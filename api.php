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
 * Enterprise AJAX API Endpoint for mod_ompdf (Auto-Bookmark & Learning Analytics).
 *
 * @package    mod_ompdf
 * @copyright  2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');

$action = required_param('action', PARAM_ALPHAEXT);
$cmid   = required_param('cmid', PARAM_INT);

$cm     = get_coursemodule_from_id('ompdf', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/ompdf:view', $context);

switch ($action) {
    case 'save_position':
        $page = required_param('page', PARAM_INT);
        set_user_preference('mod_ompdf_lastpage_' . $cm->id, $page);
        echo json_encode(['status' => 'ok', 'page' => $page]);
        break;

    case 'get_position':
        $page = (int)get_user_preferences('mod_ompdf_lastpage_' . $cm->id, 1);
        echo json_encode(['status' => 'ok', 'page' => $page]);
        break;

    case 'track_progress':
        $currentpage = required_param('page', PARAM_INT);
        $totalpages  = required_param('total', PARAM_INT);
        $duration    = optional_param('duration', 0, PARAM_INT);

        set_user_preference('mod_ompdf_lastpage_' . $cm->id, $currentpage);

        // Record time-spent reading duration per page in ompdf_analytics.
        if ($duration > 0 && $duration <= 3600 && $DB->get_manager()->table_exists('ompdf_analytics')) {
            $now = time();
            $record = $DB->get_record('ompdf_analytics', [
                'ompdfid' => $cm->instance,
                'userid'  => $USER->id,
                'page'    => $currentpage,
            ]);

            if ($record) {
                $record->duration += $duration;
                $record->timemodified = $now;
                $DB->update_record('ompdf_analytics', $record);
            } else {
                $newrec = new \stdClass();
                $newrec->ompdfid = $cm->instance;
                $newrec->userid  = $USER->id;
                $newrec->page    = $currentpage;
                $newrec->duration = $duration;
                $newrec->timecreated = $now;
                $newrec->timemodified = $now;
                $DB->insert_record('ompdf_analytics', $newrec);
            }
        }

        // Trigger automatic activity completion.
        $completion = new \completion_info($course);
        $iscompleted = false;
        if ($completion->is_enabled($cm)) {
            if ($totalpages > 0 && ($currentpage >= $totalpages || ($currentpage / $totalpages) >= 0.9)) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
                $iscompleted = true;
            }
        }
        $percentage = $totalpages > 0 ? round(($currentpage / $totalpages) * 100) : 0;
        echo json_encode(['status' => 'ok', 'progress' => $percentage, 'completed' => $iscompleted]);
        break;

    case 'save_annotation':
        $page    = required_param('page', PARAM_INT);
        $content = required_param('content', PARAM_TEXT);
        $color   = optional_param('color', 'yellow', PARAM_ALPHA);
        $type    = optional_param('type', 'student', PARAM_ALPHA);

        $isteacher = has_capability('moodle/course:manageactivities', $context);
        if ($type === 'teacher' && !$isteacher) {
            $type = 'student';
        }

        $now = time();
        $newann = new \stdClass();
        $newann->ompdfid      = $cm->instance;
        $newann->userid       = $USER->id;
        $newann->page         = $page;
        $newann->content      = $content;
        $newann->color        = $color;
        $newann->type         = $type;
        $newann->timecreated  = $now;
        $newann->timemodified = $now;

        $newid = $DB->insert_record('ompdf_annotations', $newann);
        $newann->id = $newid;
        $newann->author = fullname($USER);
        echo json_encode(['status' => 'ok', 'annotation' => $newann]);
        break;

    case 'get_annotations':
        $page = optional_param('page', 0, PARAM_INT);
        $params = ['ompdfid' => $cm->instance];
        if ($page > 0) {
            $params['page'] = $page;
        }

        $allrecords = [];
        if ($DB->get_manager()->table_exists('ompdf_annotations')) {
            $allrecords = $DB->get_records('ompdf_annotations', $params, 'timecreated ASC');
        }

        $annotationuserids = [];
        foreach ($allrecords as $record) {
            if ($record->type === 'teacher' || $record->userid == $USER->id) {
                $annotationuserids[$record->userid] = (int)$record->userid;
            }
        }
        $annotationusers = $annotationuserids ? $DB->get_records_list(
            'user',
            'id',
            array_values($annotationuserids),
            '',
            'id, firstname, lastname'
        ) : [];

        $result = [];
        foreach ($allrecords as $rec) {
            if ($rec->type === 'teacher' || $rec->userid == $USER->id) {
                $userrec = $annotationusers[$rec->userid] ?? null;
                $rec->author = $userrec ? fullname($userrec) : 'User';
                $rec->is_owner = ($rec->userid == $USER->id);
                $result[] = $rec;
            }
        }
        echo json_encode(['status' => 'ok', 'annotations' => $result]);
        break;

    case 'delete_annotation':
        $annid = required_param('id', PARAM_INT);
        $ann = $DB->get_record('ompdf_annotations', ['id' => $annid, 'ompdfid' => $cm->instance], '*', MUST_EXIST);
        $isteacher = has_capability('moodle/course:manageactivities', $context);
        if ($ann->userid == $USER->id || $isteacher) {
            $DB->delete_records('ompdf_annotations', ['id' => $annid]);
            echo json_encode(['status' => 'ok']);
        } else {
            http_response_code(403);
            echo json_encode(['error' => get_string('permission_denied', 'ompdf')]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => get_string('unknown_action', 'ompdf')]);
        break;
}
