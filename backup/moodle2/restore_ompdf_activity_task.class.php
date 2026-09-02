<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Defines the restore task for mod_ompdf activities.
 *
 * @package    mod_ompdf
 * @subpackage backup-moodle2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ompdf/backup/moodle2/restore_ompdf_stepslib.php');

/**
 * Provides the steps to restore an OMPDF activity.
 */
class restore_ompdf_activity_task extends restore_activity_task {

    /**
     * OMPDF has no activity-specific restore settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the activity structure restore step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_ompdf_activity_structure_step('ompdf_structure', 'ompdf.xml'));
    }

    /**
     * Defines links in activity content that must be decoded.
     *
     * @return restore_decode_content[] Decode content rules.
     */
    public static function define_decode_contents() {
        return array(
            new restore_decode_content('ompdf', array('intro'), 'ompdf')
        );
    }

    /**
     * Defines OMPDF link decoding rules.
     *
     * @return restore_decode_rule[] Decode rules.
     */
    public static function define_decode_rules() {
        return array(
            new restore_decode_rule('OMPDFVIEWBYID', '/mod/ompdf/view.php?id=$1', 'course_module'),
            new restore_decode_rule('OMPDFINDEX', '/mod/ompdf/index.php?id=$1', 'course')
        );
    }

    /**
     * Defines rules for restoring legacy OMPDF log entries.
     *
     * @return restore_log_rule[] Restore log rules.
     */
    public static function define_restore_log_rules() {
        return array(
            new restore_log_rule('ompdf', 'add', 'view.php?id={course_module}', '{ompdf}'),
            new restore_log_rule('ompdf', 'update', 'view.php?id={course_module}', '{ompdf}'),
            new restore_log_rule('ompdf', 'view', 'view.php?id={course_module}', '{ompdf}')
        );
    }

    /**
     * Defines rules for restoring course-level OMPDF log entries.
     *
     * @return restore_log_rule[] Restore log rules.
     */
    public static function define_restore_log_rules_for_course() {
        return array(
            new restore_log_rule('ompdf', 'view all', 'index.php?id={course}', null)
        );
    }
}
