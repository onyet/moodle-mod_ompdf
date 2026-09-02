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
 * Prints a particular instance of ompdf
 *
 * @package    mod_ompdf
 * @copyright  2021 Dian Mukti Wibowo   <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$cmid = required_param('id', PARAM_INT);

$context = context_module::instance($cmid);
$ompdf = new ompdf($context, null, null);
$cm = $ompdf->get_course_module();
$course = $ompdf->get_course();

require_login($course, true, $cm);
require_capability('mod/ompdf:view', $context);

// Redirect to course section if trying to view inline folder.
if ($ompdf->get_instance()->display == OMPDF_MANAGER_DISPLAY_INLINE) {
    // Get sectionid for fragment id section references to work.
    $sectionid = $DB->get_field(
        'course_sections',
        'section',
        ['id' => $cm->section,
                                      'course' => $course->id],
        MUST_EXIST
    );
    redirect(course_get_url($course, $sectionid));
}

$PAGE->set_pagelayout('incourse');

$url = new moodle_url('/mod/ompdf/view.php', ['id' => $cm->id]);
$PAGE->set_url($url);

// Update 'viewed' state if required by completion system.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Log viewing.
$event = \mod_ompdf\event\view_all::create([
    'objectid' => $ompdf->get_instance()->id,
    'context' => context_module::instance($cm->id),
]);
$event->trigger();

$renderer = $PAGE->get_renderer('mod_ompdf');
echo $renderer->render_ompdf($ompdf);
