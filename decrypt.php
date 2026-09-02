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
 * Direct Stream Zero-Latency Secure Token Resolution Endpoint for mod_ompdf.
 *
 * @package    mod_ompdf
 * @copyright  2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

$token = optional_param('token', '', PARAM_RAW);
if (empty($token)) {
    $token = optional_param('file', '', PARAM_RAW);
}

if (empty($token)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing security token']);
    exit;
}

$payload = \mod_ompdf\security::decrypt_payload($token);

if (!$payload || empty($payload['url'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Invalid or expired security token']);
    exit;
}

$url = $payload['url'];

if (!empty($payload['cmid'])) {
    $cmid = (int)$payload['cmid'];
    $cm = get_coursemodule_from_id('ompdf', $cmid, 0, false, IGNORE_MISSING);
    if ($cm) {
        $course = $DB->get_record('course', ['id' => $cm->course], '*', IGNORE_MISSING);
        if ($course) {
            require_login($course, true, $cm);
        }
    }
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode(['url' => $url]);
    exit;
}

// Direct stream optimization: extract pluginfile path to avoid a 302 redirect.
$pluginfilemarker = '/pluginfile.php/';
$pos = strpos($url, $pluginfilemarker);
if ($pos !== false) {
    $path = substr($url, $pos + strlen($pluginfilemarker));
    $parts = explode('/', $path);
    if (count($parts) >= 5) {
        $contextid = (int)$parts[0];
        $component = $parts[1];
        $filearea = $parts[2];
        $itemid = (int)$parts[3];
        $filename = rawurldecode(array_pop($parts));
        $filepath = '/' . trim(implode('/', array_slice($parts, 4)), '/');
        if ($filepath !== '/') {
            $filepath .= '/';
        }

        $fs = get_file_storage();
        $storedfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);

        if (!$storedfile) {
            // Fallback: match file in area by filename.
            $files = $fs->get_area_files($contextid, $component, $filearea, false, "id ASC", false);
            foreach ($files as $f) {
                if (!$f->is_directory() && $f->get_filename() === $filename) {
                    $storedfile = $f;
                    break;
                }
            }
        }

        if ($storedfile && !$storedfile->is_directory()) {
            send_stored_file($storedfile, 86400, 0, false);
            exit;
        }
    }
}

// Fallback if parsing fails or the URL is external.
redirect($url);
