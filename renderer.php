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
 * ompdf module renderering methods are defined here.
 *
 * @package    mod_ompdf
 * @copyright  2013 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ompdf/locallib.php');

/**
 * ompdf module renderer class
 */
class mod_ompdf_renderer extends plugin_renderer_base {
    /**
     * Renders the ompdf page header.
     *
     * @param ompdf $ompdf OMPDF instance.
     * @param cm_info $cm Course module information.
     * @return string Rendered page header.
     */
    public function pdf_header($ompdf, cm_info $cm) {
        $output = '';

        if (method_exists($cm, 'get_formatted_name')) {
            $name = $cm->get_formatted_name();
        } else {
            $name = format_string($cm->name, true, null);
        }
        $title = $this->page->course->shortname . ': ' . $name;

        $context = context_module::instance($cm->id);

        // Header setup.
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);

        $output .= $this->output->header();
        $output .= $this->output->heading($name, 3);

        $coursecontext = context_course::instance($cm->course);
        if (has_capability('moodle/course:manageactivities', $context) || has_capability('mod/ompdf:addinstance', $coursecontext)) {
            $analyticsurl = new moodle_url('/mod/ompdf/analytics.php', ['id' => $cm->id]);
            $output .= html_writer::div(
                html_writer::link(
                    $analyticsurl,
                    get_string('analyticslink', 'ompdf'),
                    ['class' => 'btn btn-outline-primary btn-sm mb-3']
                ),
                'text-right float-right mb-3'
            );
        }

        if (!empty($ompdf->get_instance()->intro)) {
            $output .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $output .= format_module_intro(
                'ompdf',
                $ompdf->get_instance(),
                $cm->id
            );
            $output .= $this->output->box_end();
        }

        return $output;
    }

    /**
     * Render the footer.
     *
     * @return string
     */
    public function pdf_footer() {
        return $this->output->footer();
    }

    /**
     * Render the ompdf page.
     *
     * @param ompdf $ompdf OMPDF instance.
     * @return string The page output.
     */
    public function render_ompdf($ompdf) {
        $output = '';

        $coursemodule = $ompdf->get_course_module();
        $instance = $ompdf->get_instance();
        $course = $ompdf->get_course();
        $context = $ompdf->get_context();

        // Get cm_info with uservisible.
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($coursemodule->id);

        if (
            !$cm->uservisible ||
                !has_capability('mod/ompdf:view', $context)
        ) {
            // Module is not visible to the user. Don't throw any
            // errors in renderer, just return empty string.
            return $output;
        }

        if (
            $instance->display == OMPDF_MANAGER_DISPLAY_INLINE &&
                $cm->showdescription &&
                !empty($instance->intro)
        ) {
            $output .= format_module_intro(
                'ompdf',
                $instance,
                $cm->id,
                false
            );
        }

        if ($instance->display != OMPDF_MANAGER_DISPLAY_INLINE) {
            $output .= $this->pdf_header($ompdf, $cm);
        }

        $output .= $this->pdfs($ompdf, $cm);

        if ($instance->display != OMPDF_MANAGER_DISPLAY_INLINE) {
            $output .= $this->pdf_footer($cm);
        }

        return $output;
    }

    /**
     * Utility function for getting area files
     *
     * @param int $contextid
     * @param string $areaname file area name (e.g. "pdfs")
     * @return array of stored_file objects
     */
    private function util_get_area_tree($contextid, $areaname) {
        $fs = get_file_storage();
        return $fs->get_area_tree(
            $contextid,
            'mod_ompdf',
            $areaname,
            false
        );
    }

    /**
     * Utility function for creating the pdf folder HTML.
     *
     * @param int $contextid
     * @param ompdf $ompdf
     * @param cm_info $cm
     * @return string HTML
     */
    protected function get_pdf_folder_html(
        ompdf $ompdf,
        cm_info $cm
    ) {
        $output = '';
        $tree = $this->util_get_area_tree(
            $ompdf->get_context()->id,
            'pdfs'
        );

        $tree['dirname'] = $cm->name;
        $toptree = ['files' => [],
                         'subdirs' => [$tree]];

        $openinnewtab = $ompdf->get_instance()->openinnewtab;
        $showexpanded = !empty($ompdf->get_instance()->showexpanded);
        $showdownloadlinks = $ompdf->get_default_config()->showdownloadlinks;
        $readonlyprotection = !empty($ompdf->get_instance()->readonly_protection);

        $output .= $this->output->render_from_template(
            'mod_ompdf/folder',
            $this->get_folder_template_data(
                $toptree,
                $ompdf,
                $openinnewtab,
                $showexpanded,
                $showdownloadlinks,
                $cm,
                $readonlyprotection
            )
        );

        return $output;
    }

    /**
     * Builds the data structure used by the recursive folder template.
     *
     * @param array $dir Folder data.
     * @param ompdf $ompdf OMPDF instance.
     * @param bool $openinnewtab Whether file links open in a new tab.
     * @param bool $showexpanded Whether folders are expanded by default.
     * @param bool $showdownloadlinks Whether download links are shown.
     * @param cm_info|null $cm Course module information.
     * @param bool $readonlyprotection Whether download links are disabled.
     * @return array Folder template data.
     */
    protected function get_folder_template_data(
        array $dir,
        ompdf $ompdf,
        bool $openinnewtab,
        bool $showexpanded,
        bool $showdownloadlinks,
        ?cm_info $cm,
        bool $readonlyprotection
    ): array {
        $data = ['hascontent' => !empty($dir['subdirs']) || !empty($dir['files']), 'subdirs' => [], 'files' => []];
        foreach ($dir['subdirs'] as $subdir) {
            $icon = new pix_icon(file_folder_icon(24), $subdir['dirname'], 'moodle');
            $children = $this->get_folder_template_data(
                $subdir,
                $ompdf,
                $openinnewtab,
                $showexpanded,
                $showdownloadlinks,
                $cm,
                $readonlyprotection
            );
            $data['subdirs'][] = [
                'name' => $subdir['dirname'],
                'icon' => $this->output->render($icon),
                'open' => $showexpanded,
                'subdirs' => $children['subdirs'],
                'files' => $children['files'],
                'hascontent' => $children['hascontent'],
            ];
        }

        foreach ($dir['files'] as $pdf) {
            $filename = $pdf->get_filename();
            $fileurl = moodle_url::make_pluginfile_url(
                $pdf->get_contextid(),
                $pdf->get_component(),
                $pdf->get_filearea(),
                $pdf->get_itemid(),
                $pdf->get_filepath(),
                $filename,
                false
            );
            $downloadurl = moodle_url::make_pluginfile_url(
                $pdf->get_contextid(),
                $pdf->get_component(),
                $pdf->get_filearea(),
                $pdf->get_itemid(),
                $pdf->get_filepath(),
                $filename,
                true
            );
            if (file_extension_in_typegroup($filename, 'web_image')) {
                $imageurl = $fileurl->out(false, ['preview' => 'tinyicon', 'oid' => $pdf->get_timemodified()]);
                $icon = html_writer::empty_tag('img', ['src' => $imageurl]);
                $viewerurl = $fileurl->out(false);
            } else {
                $pixicon = new pix_icon(file_file_icon($pdf, 24), $filename, 'moodle');
                $icon = $this->output->render($pixicon);
                $viewerurl = $this->get_viewer_url($fileurl, $ompdf, $cm);
            }
            $data['files'][] = [
                'filename' => $filename,
                'url' => $viewerurl,
                'viewerurl' => $viewerurl,
                'icon' => $icon,
                'newtab' => $openinnewtab,
                'download' => !$readonlyprotection && $showdownloadlinks
                    && !file_extension_in_typegroup($filename, 'web_image'),
                'downloadurl' => $downloadurl->out(false),
                'downloadtext' => get_string('downloadlinktext', 'ompdf'),
            ];
        }
        return $data;
    }

    /**
     * Builds the PDF.js viewer URL for a stored file.
     *
     * @param moodle_url $fileurl Original plugin file URL.
     * @param ompdf $ompdf OMPDF instance.
     * @param cm_info|null $cm Course module information.
     * @return moodle_url Viewer URL.
     */
    protected function get_viewer_url(
        moodle_url $fileurl,
        ompdf $ompdf,
        ?cm_info $cm
    ): moodle_url {
        $params = [];
        $plainurl = $fileurl->out(false);
        if (get_config('ompdf', 'enable_encryption') !== '0') {
            $params['file'] = \mod_ompdf\security::encrypt_url($plainurl, (int)$cm->id);
            $params['enc'] = '1';
        } else {
            $params['file'] = $plainurl;
        }
        if (
            !empty(get_config('ompdf', 'disable_print_save'))
                || !empty($ompdf->get_instance()->readonly_protection)
        ) {
            $params['drm'] = '1';
        }
        if (!empty(get_config('ompdf', 'enable_watermark'))) {
            global $USER;
            $params['wm'] = urlencode(fullname($USER) . ' | ' . get_remote_addr() . ' | ' . date('Y-m-d'));
        }
        if ($cm) {
            $params['cmid'] = (int)$cm->id;
            $params['sesskey'] = sesskey();
            $lastpage = (int)get_user_preferences('mod_ompdf_lastpage_' . $cm->id, 1);
            if ($lastpage > 1) {
                $params['lastpage'] = $lastpage;
            }
        }
        return new moodle_url('/mod/ompdf/pdfjs/web/viewer.html', $params);
    }

    /**
     * Utility function for rendering folder structure.
     *
     * @param array $tree
     * @param array $dir
     * @param boolean $openinnewtab
     * @param boolean $showdownloadlinks
     * @param cm_info|null $cm
     * @param boolean $readonlyprotection
     * @return string HTML
     */
    protected function htmlize_folder(
        $tree,
        $dir,
        $openinnewtab,
        $showdownloadlinks,
        $cm = null,
        $readonlyprotection = false
    ) {
        if (empty($dir['subdirs']) && empty($dir['files'])) {
            return '';
        }

        $cmid = $cm ? (int)$cm->id : 0;
        $output = '<ul>';

        foreach ($dir['subdirs'] as $subdir) {
            $icon = new pix_icon(
                file_folder_icon(24),
                $subdir['dirname'],
                'moodle'
            );
            $imagehtml = $this->output->render($icon);
            $iconhtml = html_writer::tag(
                'span',
                $imagehtml,
                ['class' => 'fp-icon']
            );
            $namehtml = html_writer::tag(
                'span',
                s($subdir['dirname']),
                ['class' => 'fp-filename']
            );
            $summaryhtml = html_writer::tag(
                'summary',
                $iconhtml . $namehtml,
                ['class' => 'fp-filename-icon ompdf-folder-summary']
            );

            $childrenhtml = $this->htmlize_folder(
                $tree,
                $subdir,
                $openinnewtab,
                $showdownloadlinks,
                $cm,
                $readonlyprotection
            );

            $detailsattributes = ['class' => 'ompdf-folder-details', 'open' => 'open'];
            $detailshtml = html_writer::tag(
                'details',
                $summaryhtml . $childrenhtml,
                $detailsattributes
            );

            $output .= html_writer::tag('li', $detailshtml);
        }

        foreach ($dir['files'] as $pdf) {
            $filename = $pdf->get_filename();
            $fileurl = moodle_url::make_pluginfile_url(
                $pdf->get_contextid(),
                $pdf->get_component(),
                $pdf->get_filearea(),
                $pdf->get_itemid(),
                $pdf->get_filepath(),
                $filename,
                false
            );

            $fileurlforcedownload = moodle_url::make_pluginfile_url(
                $pdf->get_contextid(),
                $pdf->get_component(),
                $pdf->get_filearea(),
                $pdf->get_itemid(),
                $pdf->get_filepath(),
                $filename,
                true
            );

            if (file_extension_in_typegroup($filename, 'web_image')) {
                $image = $fileurl->out(
                    false,
                    ['preview' => 'tinyicon',
                    'oid' => $pdf->get_timemodified()]
                );
                $image = html_writer::empty_tag('img', ['src' => $image]);
                $url = $fileurl;
                $isimage = true;
            } else {
                $icon = new pix_icon(
                    file_file_icon($pdf, 24),
                    $filename,
                    'moodle'
                );
                $image = $this->output->render($icon);

                $ompdfurl = new moodle_url('/mod/ompdf/pdfjs/web/viewer.html');
                $plainurl = $fileurl->out(false);
                $enableenc = get_config('ompdf', 'enable_encryption');
                $disableprint = get_config('ompdf', 'disable_print_save');
                $enablewatermark = get_config('ompdf', 'enable_watermark');

                $params = [];
                if ($enableenc !== '0') {
                    $params['file'] = \mod_ompdf\security::encrypt_url($plainurl, (int)$cmid);
                    $params['enc'] = '1';
                } else {
                    $params['file'] = $plainurl;
                }

                $isreadonly = !empty($disableprint) || !empty($readonlyprotection);

                if ($isreadonly) {
                    $params['drm'] = '1';
                }

                if (!empty($enablewatermark)) {
                    global $USER;
                    $wmtext = fullname($USER) . ' | ' . get_remote_addr() . ' | ' . date('Y-m-d');
                    $params['wm'] = urlencode($wmtext);
                }

                if ($cmid > 0) {
                    $params['cmid'] = $cmid;
                    $params['sesskey'] = sesskey();
                    $lastpage = (int)get_user_preferences('mod_ompdf_lastpage_' . $cmid, 1);
                    if ($lastpage > 1) {
                        $params['lastpage'] = $lastpage;
                    }
                }

                $url = new moodle_url($ompdfurl, $params);
                $isimage = false;
            }

            $linkoptions = [
                'class' => 'ompdf-preview-link',
                'data-viewer-url' => $url->out(false),
                'data-filename' => s($filename),
            ];
            if ($openinnewtab) {
                $linkoptions['target'] = '_blank';
            }

            $fileicon = html_writer::tag(
                'span',
                $image,
                ['class' => 'fp-icon']
            );
            $filenamespan = html_writer::tag(
                'span',
                $filename,
                ['class' => 'fp-filename']
            );
            $filelink = html_writer::link(
                $url,
                $fileicon . $filenamespan,
                $linkoptions
            );

            if (!$isimage && $showdownloadlinks && empty($isreadonly)) {
                $downloadlink = html_writer::link(
                    $fileurlforcedownload,
                    get_string('downloadlinktext', 'ompdf'),
                    ['target' => '_blank']
                );
                $filelink .= ' ' . html_writer::tag('em', '(' . $downloadlink . ')');
            }

            $filespan = html_writer::tag(
                'span',
                $filelink,
                ['class' => 'fp-filename-icon']
            );

            $output .= html_writer::tag('li', $filespan);
        }

        $output .= '</ul>';
        return $output;
    }

    /**
     * Renders pdfjs folder.
     *
     * @param ompdf $ompdf
     * @param cm_info $cm
     * @return string HTML
     */
    public function pdfs(ompdf $ompdf, cm_info $cm) {
        static $treecounter = 0;
        $output  = '';

        // Open folder div.
        $id = 'ompdf_manager_' . ($treecounter++);
        $output .= $this->output->container_start(
            'ompdf-onyet filemanager',
            $id
        );

        // Elements for folder.
        $output .= $this->get_pdf_folder_html($ompdf, $cm);

        // Close folder div.
        $output .= $this->output->container_end();

        // Render the preview modal from a Mustache template.
        $output .= $this->output->render_from_template('mod_ompdf/pdf_preview_modal', [
            'previewtitle' => get_string('previewtitle', 'ompdf'),
            'opennewtab' => get_string('opennewtab', 'ompdf'),
            'close' => get_string('close', 'ompdf'),
        ]);
        $showexpanded = true;
        if (empty($ompdf->get_instance()->showexpanded)) {
            $showexpanded = false;
        }

        $this->page->requires->js_call_amd('mod_ompdf/tree', 'init', [$id, $showexpanded]);
        return $output;
    }
}
