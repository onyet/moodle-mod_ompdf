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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Defines the backup task for mod_ompdf activities.
 *
 * @package    mod_ompdf
 * @subpackage backup-moodle2
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ompdf/backup/moodle2/backup_ompdf_stepslib.php');

/**
 * Provides the steps to perform one complete backup of an OMPDF instance.
 */
class backup_ompdf_activity_task extends backup_activity_task {
    /**
     * OMPDF has no activity-specific backup settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the activity structure backup step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_ompdf_activity_structure_step('ompdf_structure', 'ompdf.xml'));
    }

    /**
     * Encodes links to OMPDF activities for course restore.
     *
     * @param string $content Content that may contain OMPDF links.
     * @return string Content with portable Moodle backup links.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/ompdf\/index\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@OMPDFINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/ompdf\/view\.php\?id=)([0-9]+)/';
        return preg_replace($search, '$@OMPDFVIEWBYID*$2@$', $content);
    }
}
