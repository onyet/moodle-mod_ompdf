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
 * Defines the backup steps for mod_ompdf activities.
 *
 * @package    mod_ompdf
 * @subpackage backup-moodle2
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the complete OMPDF structure for backup.
 */
class backup_ompdf_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the activity data and its associated files.
     *
     * @return backup_nested_element The root backup structure.
     */
    protected function define_structure() {
        $ompdf = new backup_nested_element('ompdf', ['id'], [
            'name',
            'intro',
            'introformat',
            'display',
            'showexpanded',
            'openinnewtab',
            'readonly_protection',
            'timecreated',
            'timemodified',
        ]);

        $ompdf->set_source_table('ompdf', ['id' => backup::VAR_ACTIVITYID]);
        $ompdf->annotate_files('mod_ompdf', 'intro', null);
        $ompdf->annotate_files('mod_ompdf', 'pdfs', 0);

        return $this->prepare_activity_structure($ompdf);
    }
}
