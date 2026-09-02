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
     * @param ompdf ompdf
     * @return string
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
                    '📊 View Reading Analytics & Heatmap',
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
     * Render the footer
     *
     * @return string
     */
    public function pdf_footer() {
        return $this->output->footer();
    }

    /**
     * Render the ompdf page
     *
     * @param ompdf ompdf
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
        $showdownloadlinks = $ompdf->get_default_config()->showdownloadlinks;
        $readonlyprotection = !empty($ompdf->get_instance()->readonly_protection);

        $output .= $this->htmlize_folder(
            $tree,
            $toptree,
            $openinnewtab,
            $showdownloadlinks,
            $cm,
            $readonlyprotection
        );

        return $output;
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

        // OMPDF quick preview modal container.
        $output .= '
        <div class="modal fade" id="ompdfPreviewModal" tabindex="-1"
             aria-labelledby="ompdfPreviewModalLabel" aria-hidden="true"
             style="z-index: 1055;">
          <div class="modal-dialog modal-xl modal-dialog-centered"
               style="max-width: 92vw; height: 90vh;">
            <div class="modal-content"
                 style="height: 100%; border-radius: 12px; overflow: hidden;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.35); border: none;">
              <div class="modal-header"
                   style="background: #1e293b; color: #fff;
                          padding: 0.75rem 1.25rem;">
                <h5 class="modal-title d-flex align-items-center gap-2"
                    id="ompdfPreviewModalLabel"
                    style="font-size: 1.1rem; font-weight: 600; margin: 0;">
                  <span>📄 PDF Quick Preview</span>
                </h5>
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <a id="ompdfOpenNewTabBtn" href="#" target="_blank"
                     class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                     style="font-size: 0.85rem; font-weight: 500;">
                    <span>↗️ Open Fullscreen in New Tab</span>
                  </a>
                  <button type="button" id="ompdfCloseModalBtn"
                          class="btn btn-sm btn-outline-light" aria-label="Close"
                          style="font-size: 1.1rem; font-weight: bold; line-height: 1;
                                 padding: 2px 8px; border-radius: 4px; cursor: pointer;
                                 color: #ffffff; background: rgba(255,255,255,0.15);
                                 border: 1px solid rgba(255,255,255,0.3);">✕</button>
                </div>
              </div>
              <div class="modal-body p-0" style="background: #0f172a; flex: 1; position: relative; height: calc(100% - 56px);">
                <iframe id="ompdfPreviewIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
              </div>
            </div>
          </div>
        </div>
        <script>
        (function() {
            window.closeOmpdfModal = function() {
                var modal = document.getElementById("ompdfPreviewModal");
                var iframe = document.getElementById("ompdfPreviewIframe");
                var backdrop = document.getElementById("ompdfModalBackdrop");

                if (iframe) iframe.src = "";
                if (modal) {
                    modal.style.display = "none";
                    modal.classList.remove("show");
                }
                if (backdrop) {
                    backdrop.style.display = "none";
                    backdrop.remove();
                }
                document.body.classList.remove("modal-open");
            };

            var initModal = function() {
                var container = document.getElementById("' . $id . '");
                if (!container) return;

                container.addEventListener("click", function(e) {
                    var link = e.target.closest(".ompdf-preview-link");
                    if (!link) return;
                    var viewerUrl = link.getAttribute("data-viewer-url");
                    var filename = link.getAttribute("data-filename");
                    if (!viewerUrl) return;

                    e.preventDefault();
                    e.stopPropagation();

                    var modal = document.getElementById("ompdfPreviewModal");
                    var iframe = document.getElementById("ompdfPreviewIframe");
                    var openNewTabBtn = document.getElementById("ompdfOpenNewTabBtn");
                    var titleEl = document.getElementById("ompdfPreviewModalLabel");

                    if (modal && iframe) {
                        iframe.src = viewerUrl;
                        if (openNewTabBtn) openNewTabBtn.href = viewerUrl;
                        if (titleEl) titleEl.textContent = "📄 " + (filename || "PDF Preview");

                        modal.style.display = "block";
                        modal.classList.add("show");
                        document.body.classList.add("modal-open");

                        var backdrop = document.getElementById("ompdfModalBackdrop");
                        if (!backdrop) {
                            backdrop = document.createElement("div");
                            backdrop.id = "ompdfModalBackdrop";
                            backdrop.className = "modal-backdrop fade show";
                            document.body.appendChild(backdrop);
                        }
                        backdrop.style.display = "block";
                    } else {
                        window.open(viewerUrl, "_blank");
                    }
                }, true);
            };

            document.addEventListener("click", function(e) {
                if (e.target.closest("#ompdfCloseModalBtn") ||
                    e.target.closest("#ompdfOpenNewTabBtn") ||
                    e.target.closest("#ompdfModalBackdrop") ||
                    e.target.closest("[data-bs-dismiss=\"modal\"]") ||
                    e.target.closest("[data-dismiss=\"modal\"]") ||
                    (e.target.id === "ompdfPreviewModal")) {
                    window.closeOmpdfModal();
                }
            });

            document.addEventListener("keydown", function(e) {
                if (e.key === "Escape" || e.keyCode === 27) {
                    window.closeOmpdfModal();
                }
            });

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initModal);
            } else {
                initModal();
            }
        })();
        </script>
        ';

        $showexpanded = true;
        if (empty($ompdf->get_instance()->showexpanded)) {
            $showexpanded = false;
        }

        $this->page->requires->js_call_amd('mod_ompdf/tree', 'init', [$id, $showexpanded]);
        return $output;
    }
}
