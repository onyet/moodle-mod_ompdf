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
 * Defines the restore steps for mod_ompdf activities.
 *
 * @package    mod_ompdf
 * @subpackage backup-moodle2
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores one OMPDF activity.
 */
class restore_ompdf_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the XML paths used during restore.
     *
     * @return restore_path_element[] Restore paths.
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('ompdf', '/activity/ompdf');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Inserts the restored activity record.
     *
     * @param array|stdClass $data Restored activity data.
     */
    protected function process_ompdf($data) {
        global $DB;

        $data = (object)$data;
        unset($data->course);
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('ompdf', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores the activity introduction and PDF files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_ompdf', 'intro', null);
        $this->add_related_files('mod_ompdf', 'pdfs', 0);
    }
}
