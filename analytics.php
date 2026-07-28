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
 * Teacher Reading Heatmap & Engagement Analytics Dashboard.
 *
 * @package    mod_ompdf
 * @copyright  2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$export = optional_param('export', '', PARAM_ALPHA);

$cm = null;
$modid = $DB->get_field('modules', 'id', array('name' => 'ompdf'));
if ($DB->record_exists('course_modules', array('id' => $id, 'module' => $modid))) {
    $cm = get_coursemodule_from_id('ompdf', $id, 0, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_instance('ompdf', $id, 0, false, MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$ompdf = $DB->get_record('ompdf', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

if (!has_capability('moodle/course:manageactivities', $context) && !has_capability('mod/ompdf:addinstance', $coursecontext)) {
    require_capability('moodle/course:manageactivities', $context);
}

// Fetch enrolled students using course context
$students = get_enrolled_users($coursecontext, 'mod/ompdf:view');
if (empty($students)) {
    $students = array();
}

// Fetch analytics records for this ompdf instance
$records = array();
if ($DB->get_manager()->table_exists('ompdf_analytics')) {
    $records = $DB->get_records('ompdf_analytics', array('ompdfid' => $ompdf->id));
}

// Ensure any active reader (student or test user) appears in the analytics table
foreach ($records as $rec) {
    if (!isset($students[$rec->userid])) {
        $u = $DB->get_record('user', array('id' => $rec->userid), 'id, firstname, lastname, email', IGNORE_MISSING);
        if ($u) {
            $students[$rec->userid] = $u;
        }
    }
}

// Process Heatmap Data per Page
$pageheatmap = array(); // page => ['total_duration' => X, 'readers' => Y]
$studentdata = array(); // userid => ['pages_read' => X, 'total_duration' => Y, 'last_modified' => Z]

foreach ($records as $rec) {
    // Heatmap aggregation
    if (!isset($pageheatmap[$rec->page])) {
        $pageheatmap[$rec->page] = array('total_duration' => 0, 'readers' => array());
    }
    $pageheatmap[$rec->page]['total_duration'] += $rec->duration;
    $pageheatmap[$rec->page]['readers'][$rec->userid] = true;

    // Student aggregation
    if (!isset($studentdata[$rec->userid])) {
        $studentdata[$rec->userid] = array('pages' => array(), 'total_duration' => 0, 'last_modified' => 0);
    }
    $studentdata[$rec->userid]['pages'][$rec->page] = true;
    $studentdata[$rec->userid]['total_duration'] += $rec->duration;
    if ($rec->timemodified > $studentdata[$rec->userid]['last_modified']) {
        $studentdata[$rec->userid]['last_modified'] = $rec->timemodified;
    }
}
ksort($pageheatmap);

// Handle CSV Export
if ($export === 'csv') {
    $filename = 'ompdf_analytics_' . clean_filename($ompdf->name) . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Student Name', 'Email', 'Pages Read', 'Total Time (Seconds)', 'Total Time (Formatted)', 'Last Active Date'));

    foreach ($students as $student) {
        $data = isset($studentdata[$student->id]) ? $studentdata[$student->id] : array('pages' => array(), 'total_duration' => 0, 'last_modified' => 0);
        $pagesread = count($data['pages']);
        $totalsec = $data['total_duration'];
        $formattedtime = sprintf('%02dh %02dm %02ds', floor($totalsec / 3600), floor(($totalsec % 3600) / 60), $totalsec % 60);
        $lastactive = $data['last_modified'] ? userdate($data['last_modified']) : 'Never';

        fputcsv($output, array(
            fullname($student),
            $student->email,
            $pagesread,
            $totalsec,
            $formattedtime,
            $lastactive
        ));
    }
    fclose($output);
    exit;
}

// Render HTML Dashboard Page
$PAGE->set_url('/mod/ompdf/analytics.php', array('id' => $cm->id));
$PAGE->set_title(format_string($ompdf->name) . ': Analytics Dashboard');
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// Page Header & Controls
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-4');
echo html_writer::tag('h2', '📊 Reading Analytics & Heatmap: ' . format_string($ompdf->name));
echo html_writer::link(
    new moodle_url('/mod/ompdf/analytics.php', array('id' => $cm->id, 'export' => 'csv')),
    '📥 Export Report (CSV)',
    array('class' => 'btn btn-primary')
);
echo html_writer::end_div();

// Summary Cards
$totalreaders = count($studentdata);
$totalseconds = 0;
foreach ($studentdata as $sd) { $totalseconds += $sd['total_duration']; }
$avgduration = $totalreaders > 0 ? round($totalseconds / $totalreaders) : 0;
$avgformatted = sprintf('%02dm %02ds', floor($avgduration / 60), $avgduration % 60);

echo html_writer::start_div('row mb-4');
echo '<div class="col-md-4"><div class="card text-white bg-info mb-3"><div class="card-body"><h5 class="card-title">Total Active Readers</h5><p class="card-text display-4">' . $totalreaders . '</p></div></div></div>';
echo '<div class="col-md-4"><div class="card text-white bg-success mb-3"><div class="card-body"><h5 class="card-title">Total Cumulative Time</h5><p class="card-text display-4">' . sprintf('%02dh %02dm', floor($totalseconds/3600), floor(($totalseconds%3600)/60)) . '</p></div></div></div>';
echo '<div class="col-md-4"><div class="card text-white bg-dark mb-3"><div class="card-body"><h5 class="card-title">Avg Time per Student</h5><p class="card-text display-4">' . $avgformatted . '</p></div></div></div>';
echo html_writer::end_div();

// Reading Heatmap Section
echo html_writer::start_div('card mb-4');
echo html_writer::div('🔥 Page Reading Heatmap (Time Spent per Page)', 'card-header font-weight-bold');
echo html_writer::start_div('card-body');

if (empty($pageheatmap)) {
    echo html_writer::div('No reading engagement analytics recorded yet.', 'alert alert-secondary');
} else {
    $maxduration = 1;
    foreach ($pageheatmap as $ph) {
        if ($ph['total_duration'] > $maxduration) $maxduration = $ph['total_duration'];
    }

    echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
    foreach ($pageheatmap as $page => $pdata) {
        $pct = round(($pdata['total_duration'] / $maxduration) * 100);
        $readercount = count($pdata['readers']);
        $durformatted = sprintf('%02dm %02ds', floor($pdata['total_duration'] / 60), $pdata['total_duration'] % 60);

        // Color coding heatmap intensity
        $color = '#3b82f6'; // Blue
        if ($pct > 75) $color = '#ef4444'; // Red (hotspot)
        else if ($pct > 40) $color = '#f59e0b'; // Amber

        echo '<div style="display: flex; align-items: center; gap: 15px;">';
        echo '<div style="width: 70px; font-weight: bold;">Page ' . $page . '</div>';
        echo '<div style="flex-grow: 1; background: #e2e8f0; height: 24px; border-radius: 4px; overflow: hidden;">';
        echo '<div style="width: ' . max($pct, 4) . '%; background: ' . $color . '; height: 100%; transition: width 0.5s;"></div>';
        echo '</div>';
        echo '<div style="width: 180px; text-align: right; font-size: 0.9rem; color: #475569;">' . $durformatted . ' (' . $readercount . ' readers)</div>';
        echo '</div>';
    }
    echo '</div>';
}
echo html_writer::end_div();
echo html_writer::end_div();

// Student Engagement Table
echo html_writer::start_div('card');
echo html_writer::div('👥 Student Engagement Breakdown', 'card-header font-weight-bold');
echo html_writer::start_div('card-body');

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = array('Student Name', 'Email', 'Pages Read', 'Total Time Spent', 'Last Active');

foreach ($students as $student) {
    $sdata = isset($studentdata[$student->id]) ? $studentdata[$student->id] : array('pages' => array(), 'total_duration' => 0, 'last_modified' => 0);
    $pagesread = count($sdata['pages']);
    $totalsec = $sdata['total_duration'];
    $formattedtime = sprintf('%02dm %02ds', floor($totalsec / 60), $totalsec % 60);
    $lastactive = $sdata['last_modified'] ? userdate($sdata['last_modified']) : 'Never';

    $table->data[] = array(
        fullname($student),
        $student->email,
        $pagesread > 0 ? '<span class="badge bg-success">' . $pagesread . ' pages</span>' : '<span class="badge bg-secondary">0 pages</span>',
        $formattedtime,
        $lastactive
    );
}

echo html_writer::table($table);
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
