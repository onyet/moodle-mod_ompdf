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

require_once(dirname(__FILE__) . '/../../config.php');

require_login();
header('Content-Type: application/json');

$action = required_param('action', PARAM_ALPHAEXT);
$cmid   = required_param('cmid', PARAM_INT);

$cm     = get_coursemodule_from_id('ompdf', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

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

        set_user_preference('mod_ompdf_lastpage_' . $cm->id, $currentpage);

        // Auto activity completion trigger
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

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        break;
}
