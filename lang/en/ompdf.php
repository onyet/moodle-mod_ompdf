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
 * English strings for ompdf.
 *
 * @package    mod_ompdf
 * @copyright  2013 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'OMPDF';
$string['modulenameplural'] = 'OMPDFs';
$string['modulename_help'] = 'A folder plugin built on PDF.js with the goal of making sure that the PDFs in the folder always open in the browser (with the option of downloading).';

$string['ompdf:addinstance'] = 'Add a new OMPDF';
$string['ompdf:view'] = 'View OMPDF';

$string['pluginadministration'] = 'OMPDF administration';
$string['pluginname'] = 'OMPDF';

$string['ompdf_defaults_heading'] = 'Default values for OMPDF settings';
$string['ompdf_defaults_text'] = 'The values you set here define the default values that are used in the OMPDF settings form when you create a new OMPDF.';
$string['ompdf_options_heading'] = 'OMPDF options';
$string['ompdf_options_text'] = 'The values you set here change how OMPDFs work or are displayed.';

$string['filearea_pdfs'] = 'PDFs';

$string['pdf_fieldset'] = 'PDF';

$string['pdfs'] = 'PDFs';
$string['pdfs_help'] = 'Add the PDF files here.';

$string['display'] = 'Display folder contents';
$string['display_help'] = "If you choose to display the folder contents on a course page, there will be no link to a separate page. The description will be displayed only if \"Display description on course page\" is checked.\n\nAlso note that participants view actions can not be logged in this case.";
$string['displaypage'] = 'On a separate page';
$string['displayinline'] = 'Inline on a course page';
$string['downloadlinktext'] = 'download';
$string['analyticslink'] = '📊 View Reading Analytics & Heatmap';
$string['analytics_title'] = '📊 Reading Analytics & Heatmap: {$a}';
$string['export_report'] = '📥 Export Report (CSV)';
$string['csv_student_name'] = 'Student Name';
$string['csv_email'] = 'Email';
$string['csv_pages_read'] = 'Pages Read';
$string['csv_total_time_seconds'] = 'Total Time (Seconds)';
$string['csv_total_time_formatted'] = 'Total Time (Formatted)';
$string['csv_last_active_date'] = 'Last Active Date';
$string['never'] = 'Never';
$string['total_active_readers'] = 'Total Active Readers';
$string['total_cumulative_time'] = 'Total Cumulative Time';
$string['average_time_per_student'] = 'Avg Time per Student';
$string['page_reading_heatmap'] = '🔥 Page Reading Heatmap (Time Spent per Page)';
$string['no_reading_analytics'] = 'No reading engagement analytics recorded yet.';
$string['page'] = 'Page {$a}';
$string['readers'] = '{$a} readers';
$string['student_engagement'] = '👥 Student Engagement Breakdown';
$string['total_time_spent'] = 'Total Time Spent';
$string['last_active'] = 'Last Active';
$string['pages'] = '{$a} pages';
$string['permission_denied'] = 'Permission denied';
$string['unknown_action'] = 'Unknown action';
$string['noautocompletioninline'] = 'Automatic completion on viewing of activity can not be selected together with "Display inline" option.';
$string['showexpanded'] = 'Show sub-folders expanded';
$string['showexpanded_help'] = 'If enabled, will display sub-folders expanded by default. Else, sub-folders will display collapsed.';
$string['openinnewtab'] = 'Open PDFs in new tabs/windows';
$string['openinnewtab_help'] = 'If enabled, PDFs will open in new tabs or windows rather than in the current tab or window.';
$string['security_hdr'] = 'Security & DRM Protection';
$string['readonly_protection'] = 'Enable Read-Only Protection (Disable Download & Print)';
$string['readonly_protection_help'] = 'If enabled, students can only view the PDF inside the reader. Downloading, printing, saving, copying text, right-click context menus, and screen recording/OBS will be blocked.';
$string['showdownloadlinks'] = 'Show links for downloading PDFs';
$string['showdownloadlinks_help'] = "If enabled, each PDF.js-based link will be followed by a link to download the PDF.\n\nThis can be useful on mobile devices where PDF.js may use too much memory or be too slow to work satisfactorily.";
$string['eventviewall'] = "View All";

$string['enable_encryption'] = 'Enable AES-256 URL Encryption';
$string['enable_encryption_help'] = 'If enabled, PDF target URLs will be encrypted with AES-256-CBC and time-bound security tokens to prevent hotlinking and direct URL exposure.';
$string['disable_print_save'] = 'Disable Print, Save & Context Menu (DRM Protection)';
$string['disable_print_save_help'] = 'If enabled, keyboard shortcuts for print/save (Ctrl+P/S, Cmd+P/S) and right-click context menus are disabled inside the PDF viewer.';
$string['enable_watermark'] = 'Enable Dynamic User Watermarking';
$string['enable_watermark_help'] = 'If enabled, a translucent watermark containing the user\'s name, IP address, and date will be rendered across the PDF pages.';

$string['privacy:metadata:ompdf_analytics'] = 'Reading activity analytics stored for each OMPDF user.';
$string['privacy:metadata:ompdf_analytics:userid'] = 'The user associated with the reading analytics.';
$string['privacy:metadata:ompdf_analytics:ompdfid'] = 'The OMPDF activity associated with the reading analytics.';
$string['privacy:metadata:ompdf_analytics:page'] = 'The PDF page that was viewed.';
$string['privacy:metadata:ompdf_analytics:duration'] = 'The recorded reading duration in seconds.';
$string['privacy:metadata:ompdf_analytics:timecreated'] = 'The time the analytics record was created.';
$string['privacy:metadata:ompdf_analytics:timemodified'] = 'The time the analytics record was last modified.';
$string['privacy:metadata:ompdf_annotations'] = 'Notes and hints stored for OMPDF users.';
$string['privacy:metadata:ompdf_annotations:userid'] = 'The user who created the annotation.';
$string['privacy:metadata:ompdf_annotations:ompdfid'] = 'The OMPDF activity associated with the annotation.';
$string['privacy:metadata:ompdf_annotations:page'] = 'The PDF page associated with the annotation.';
$string['privacy:metadata:ompdf_annotations:content'] = 'The text content of the annotation.';
$string['privacy:metadata:ompdf_annotations:color'] = 'The display colour assigned to the annotation.';
$string['privacy:metadata:ompdf_annotations:type'] = 'The type of annotation.';
$string['privacy:metadata:ompdf_annotations:timecreated'] = 'The time the annotation was created.';
$string['privacy:metadata:ompdf_annotations:timemodified'] = 'The time the annotation was last modified.';
$string['privacy:metadata:mod_ompdf_lastpage'] = 'The last PDF page visited by the user in an OMPDF activity.';
