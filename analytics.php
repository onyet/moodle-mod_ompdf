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
$modid = $DB->get_field('modules', 'id', ['name' => 'ompdf']);
if ($DB->record_exists('course_modules', ['id' => $id, 'module' => $modid])) {
    $cm = get_coursemodule_from_id('ompdf', $id, 0, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_instance('ompdf', $id, 0, false, MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ompdf = $DB->get_record('ompdf', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

if (!has_capability('moodle/course:manageactivities', $context) && !has_capability('mod/ompdf:addinstance', $coursecontext)) {
    require_capability('moodle/course:manageactivities', $context);
}

// Fetch enrolled students using the course context.
$students = get_enrolled_users($coursecontext, 'mod/ompdf:view');
if (empty($students)) {
    $students = [];
}

// Fetch analytics records for this OMPDF instance.
$records = [];
if ($DB->get_manager()->table_exists('ompdf_analytics')) {
    $records = $DB->get_records('ompdf_analytics', ['ompdfid' => $ompdf->id]);
}

// Ensure any active reader appears in the analytics table.
foreach ($records as $rec) {
    if (!isset($students[$rec->userid])) {
        $u = $DB->get_record('user', ['id' => $rec->userid], 'id, firstname, lastname, email', IGNORE_MISSING);
        if ($u) {
            $students[$rec->userid] = $u;
        }
    }
}

// Process heatmap data per page.
$pageheatmap = [];
$studentdata = [];

foreach ($records as $rec) {
    // Aggregate heatmap data.
    if (!isset($pageheatmap[$rec->page])) {
        $pageheatmap[$rec->page] = ['total_duration' => 0, 'readers' => []];
    }
    $pageheatmap[$rec->page]['total_duration'] += $rec->duration;
    $pageheatmap[$rec->page]['readers'][$rec->userid] = true;

    // Aggregate student data.
    if (!isset($studentdata[$rec->userid])) {
        $studentdata[$rec->userid] = ['pages' => [], 'total_duration' => 0, 'last_modified' => 0];
    }
    $studentdata[$rec->userid]['pages'][$rec->page] = true;
    $studentdata[$rec->userid]['total_duration'] += $rec->duration;
    if ($rec->timemodified > $studentdata[$rec->userid]['last_modified']) {
        $studentdata[$rec->userid]['last_modified'] = $rec->timemodified;
    }
}
ksort($pageheatmap);

// Handle CSV export.
if ($export === 'csv') {
    $filename = 'ompdf_analytics_' . clean_filename($ompdf->name) . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        get_string('csv_student_name', 'ompdf'),
        get_string('csv_email', 'ompdf'),
        get_string('csv_pages_read', 'ompdf'),
        get_string('csv_total_time_seconds', 'ompdf'),
        get_string('csv_total_time_formatted', 'ompdf'),
        get_string('csv_last_active_date', 'ompdf'),
    ]);

    foreach ($students as $student) {
        $data = isset($studentdata[$student->id])
            ? $studentdata[$student->id]
            : ['pages' => [], 'total_duration' => 0, 'last_modified' => 0];
        $pagesread = count($data['pages']);
        $totalsec = $data['total_duration'];
        $formattedtime = sprintf('%02dh %02dm %02ds', floor($totalsec / 3600), floor(($totalsec % 3600) / 60), $totalsec % 60);
        $lastactive = $data['last_modified'] ? userdate($data['last_modified']) : get_string('never', 'ompdf');

        fputcsv($output, [
            fullname($student),
            $student->email,
            $pagesread,
            $totalsec,
            $formattedtime,
            $lastactive,
        ]);
    }
    fclose($output);
    exit;
}

// Render HTML dashboard page.
$PAGE->set_url('/mod/ompdf/analytics.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('analytics_title', 'ompdf', format_string($ompdf->name)));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// Page header and controls.
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-4');
echo html_writer::tag('h2', get_string('analytics_title', 'ompdf', format_string($ompdf->name)));
echo html_writer::link(
    new moodle_url('/mod/ompdf/analytics.php', ['id' => $cm->id, 'export' => 'csv']),
    get_string('export_report', 'ompdf'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();

// Summary cards.
$totalreaders = count($studentdata);
$totalseconds = 0;
foreach ($studentdata as $sd) {
    $totalseconds += $sd['total_duration'];
}
$avgduration = $totalreaders > 0 ? round($totalseconds / $totalreaders) : 0;
$avgformatted = sprintf('%02dm %02ds', floor($avgduration / 60), $avgduration % 60);

echo html_writer::start_div('row mb-4');
    echo html_writer::div(
        html_writer::div(html_writer::tag('h5', get_string('total_active_readers', 'ompdf'), ['class' => 'card-title'])
        . html_writer::tag('p', $totalreaders, ['class' => 'card-text display-4']), 'card-body'),
        'card text-white bg-info mb-3'
    );
    echo html_writer::div(
        html_writer::div(html_writer::tag('h5', get_string('total_cumulative_time', 'ompdf'), ['class' => 'card-title'])
        . html_writer::tag(
            'p',
            sprintf('%02dh %02dm', floor($totalseconds / 3600), floor(($totalseconds % 3600) / 60)),
            ['class' => 'card-text display-4']
        ), 'card-body'),
        'card text-white bg-success mb-3'
    );
    echo html_writer::div(
        html_writer::div(html_writer::tag('h5', get_string('average_time_per_student', 'ompdf'), ['class' => 'card-title'])
        . html_writer::tag('p', $avgformatted, ['class' => 'card-text display-4']), 'card-body'),
        'card text-white bg-dark mb-3'
    );
    echo html_writer::end_div();

    // Reading heatmap section.
    echo html_writer::start_div('card mb-4');
    echo html_writer::div(get_string('page_reading_heatmap', 'ompdf'), 'card-header font-weight-bold');
    echo html_writer::start_div('card-body');

    if (empty($pageheatmap)) {
        echo html_writer::div(get_string('no_reading_analytics', 'ompdf'), 'alert alert-secondary');
    } else {
        $maxduration = 1;
        foreach ($pageheatmap as $ph) {
            if ($ph['total_duration'] > $maxduration) {
                $maxduration = $ph['total_duration'];
            }
        }

        echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
        foreach ($pageheatmap as $page => $pdata) {
            $pct = round(($pdata['total_duration'] / $maxduration) * 100);
            $readercount = count($pdata['readers']);
            $durformatted = sprintf('%02dm %02ds', floor($pdata['total_duration'] / 60), $pdata['total_duration'] % 60);

            // Set heatmap intensity color.
            $color = '#3b82f6';
            if ($pct > 75) {
                $color = '#ef4444';
            } else if ($pct > 40) {
                $color = '#f59e0b';
            }

            echo '<div style="display: flex; align-items: center; gap: 15px;">';
            echo '<div style="width: 70px; font-weight: bold;">' . get_string('page', 'ompdf', $page) . '</div>';
            echo '<div style="flex-grow: 1; background: #e2e8f0; height: 24px; border-radius: 4px; overflow: hidden;">';
            echo '<div style="width: ' . max($pct, 4) . '%; background: ' . $color
                . '; height: 100%; transition: width 0.5s;"></div>';
            echo '</div>';
            echo '<div style="width: 180px; text-align: right; font-size: 0.9rem; '
                . 'color: #475569;">' . $durformatted . ' (' . get_string('readers', 'ompdf', $readercount) . ')</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Student engagement table.
    echo html_writer::start_div('card');
    echo html_writer::div(get_string('student_engagement', 'ompdf'), 'card-header font-weight-bold');
    echo html_writer::start_div('card-body');

    $table = new html_table();
    $table->attributes['class'] = 'generaltable mod_index';
    $table->head = [
        get_string('csv_student_name', 'ompdf'),
        get_string('csv_email', 'ompdf'),
        get_string('csv_pages_read', 'ompdf'),
        get_string('total_time_spent', 'ompdf'),
        get_string('last_active', 'ompdf'),
    ];

    foreach ($students as $student) {
        $sdata = isset($studentdata[$student->id])
            ? $studentdata[$student->id]
            : ['pages' => [], 'total_duration' => 0, 'last_modified' => 0];
        $pagesread = count($sdata['pages']);
        $totalsec = $sdata['total_duration'];
        $formattedtime = sprintf('%02dm %02ds', floor($totalsec / 60), $totalsec % 60);
        $lastactive = $sdata['last_modified'] ? userdate($sdata['last_modified']) : get_string('never', 'ompdf');

        $table->data[] = [
        fullname($student),
        $student->email,
        $pagesread > 0
            ? '<span class="badge bg-success">' . get_string('pages', 'ompdf', $pagesread) . '</span>'
            : '<span class="badge bg-secondary">' . get_string('pages', 'ompdf', 0) . '</span>',
        $formattedtime,
        $lastactive,
        ];
    }

    echo html_writer::table($table);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo $OUTPUT->footer();
