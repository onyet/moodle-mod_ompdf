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
 * Lists OMPDF activities in a course.
 *
 * @package    mod_ompdf
 * @copyright  2013 Dian Mukti Wibowo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$event = \mod_ompdf\event\view_all::create([
    'courseid' => $course->id,
    'context' => context_course::instance($course->id),
]);
$event->trigger();

$strpdf          = get_string('modulename', 'ompdf');
$strpdfs         = get_string('modulenameplural', 'ompdf');
$strsectionname  = get_string('sectionname', 'format_' . $course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/ompdf/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname . ': ' . $strpdfs);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strpdfs);
echo $OUTPUT->header();

if (!$pdffolders = get_all_instances_in_course('ompdf', $course)) {
    notice(
        get_string(
            'thereareno',
            'moodle',
            $strpdfs
        ),
        "$CFG->wwwroot/course/view.php?id=$course->id"
    );
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = [$strsectionname, $strname, $strintro];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head  = [$strlastmodified, $strname, $strintro];
    $table->align = ['left', 'left', 'left'];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($pdffolders as $pdffolder) {
    $cm = $modinfo->cms[$pdffolder->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($pdffolder->section !== $currentsection) {
            if ($pdffolder->section) {
                $printsection = get_section_name($course, $pdffolder->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $pdffolder->section;
        }
    } else {
        $printsection = '<span class="smallinfo">' . userdate($pdffolder->timemodified) . "</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        $iconurl = method_exists($cm, 'get_icon_url') ? $cm->get_icon_url() : $OUTPUT->pix_url($cm->icon);
        $icon = html_writer::empty_tag('img', [
            'src' => $iconurl,
            'class' => 'activityicon',
            'alt' => get_string('modulename', $cm->modname),
        ]) . ' ';
    }
    // Dim hidden modules.
    $class = $pdffolder->visible ? '' : 'class="dimmed"';
    $table->data[] = [
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">" .
            $icon . format_string($pdffolder->name) . "</a>",
        format_module_intro('ompdf', $pdffolder, $cm->id)];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
