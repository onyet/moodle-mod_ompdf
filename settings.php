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
 * ompdf module admin settings and defaults.
 *
 * @package    mod_ompdf
 * @copyright  2013 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->libdir . '/resourcelib.php');

    $displayoptions = resourcelib_get_displayoptions(
        array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    // Options heading.
    $settings->add(
        new admin_setting_heading('ompdf_options',
                                  get_string('ompdf_options_heading', 'ompdf'),
                                  get_string('ompdf_options_text', 'ompdf')));

    // Flag for whether to show download links or not.
    $settings->add(
        new admin_setting_configcheckbox('ompdf/showdownloadlinks',
                                         get_string('showdownloadlinks', 'ompdf'),
                                         get_string('showdownloadlinks_help', 'ompdf'),
                                         1));

    // Defaults heading.
    $settings->add(
        new admin_setting_heading('ompdf_defaults',
                                  get_string('ompdf_defaults_heading', 'ompdf'),
                                  get_string('ompdf_defaults_text', 'ompdf')));

    // Default show expanded flag.
    $settings->add(
        new admin_setting_configcheckbox('ompdf/showexpanded',
                                         get_string('showexpanded', 'ompdf'),
                                         get_string('showexpanded_help', 'ompdf'),
                                         1));

    // Default open in new window/tab flag.
    $settings->add(
        new admin_setting_configcheckbox('ompdf/openinnewtab',
                                         get_string('openinnewtab', 'ompdf'),
                                         get_string('openinnewtab_help', 'ompdf'),
                                         1));

    // AES-256 Encryption setting.
    $settings->add(
        new admin_setting_configcheckbox('ompdf/enable_encryption',
                                         get_string('enable_encryption', 'ompdf'),
                                         get_string('enable_encryption_help', 'ompdf'),
                                         1));

    // DRM protection setting (Disable print/save/contextmenu).
    $settings->add(
        new admin_setting_configcheckbox('ompdf/disable_print_save',
                                         get_string('disable_print_save', 'ompdf'),
                                         get_string('disable_print_save_help', 'ompdf'),
                                         0));

    // Dynamic Watermark setting.
    $settings->add(
        new admin_setting_configcheckbox('ompdf/enable_watermark',
                                         get_string('enable_watermark', 'ompdf'),
                                         get_string('enable_watermark_help', 'ompdf'),
                                         0));
}
