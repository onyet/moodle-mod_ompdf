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
 * Backup and restore API smoke tests.
 *
 * @package    mod_ompdf
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ompdf\backup;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Ensures both activity task classes are registered and extend Moodle APIs.
 *
 * @covers \backup_ompdf_activity_task
 * @covers \restore_ompdf_activity_task
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Test the activity task inheritance contract.
     */
    public function test_activity_task_classes(): void {
        global $CFG;

        require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
        require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');
        require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');
        require_once($CFG->dirroot . '/mod/ompdf/backup/moodle2/backup_ompdf_activity_task.class.php');
        require_once($CFG->dirroot . '/mod/ompdf/backup/moodle2/restore_ompdf_activity_task.class.php');

        $this->assertTrue(is_subclass_of('backup_ompdf_activity_task', 'backup_activity_task'));
        $this->assertTrue(is_subclass_of('restore_ompdf_activity_task', 'restore_activity_task'));
    }
}
