<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Defines the backup steps for mod_ompdf activities.
 *
 * @package    mod_ompdf
 * @subpackage backup-moodle2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
        $ompdf = new backup_nested_element('ompdf', array('id'), array(
            'name',
            'intro',
            'introformat',
            'display',
            'showexpanded',
            'openinnewtab',
            'readonly_protection',
            'timecreated',
            'timemodified'
        ));

        $ompdf->set_source_table('ompdf', array('id' => backup::VAR_ACTIVITYID));
        $ompdf->annotate_files('mod_ompdf', 'intro', null);
        $ompdf->annotate_files('mod_ompdf', 'pdfs', 0);

        return $this->prepare_activity_structure($ompdf);
    }
}
