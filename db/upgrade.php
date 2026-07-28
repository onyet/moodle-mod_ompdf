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
 * This file keeps track of upgrades to the ompdf module.
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_ompdf
 * @copyright  2013 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute ompdf upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_ompdf_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2026072800) {
        $table = new xmldb_table('ompdf_analytics');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ompdfid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('page', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('ompdfid_user_page', XMLDB_INDEX_NOTUNIQUE, array('ompdfid', 'userid', 'page'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $ompdf_table = new xmldb_table('ompdf');
        $field = new xmldb_field('readonly_protection', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'openinnewtab');
        if (!$dbman->field_exists($ompdf_table, $field)) {
            $dbman->add_field($ompdf_table, $field);
        }

        $ann_table = new xmldb_table('ompdf_annotations');
        $ann_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $ann_table->add_field('ompdfid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ann_table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ann_table->add_field('page', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $ann_table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $ann_table->add_field('color', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'yellow');
        $ann_table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'student');
        $ann_table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $ann_table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $ann_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $ann_table->add_index('ompdfid_page', XMLDB_INDEX_NOTUNIQUE, array('ompdfid', 'page'));

        if (!$dbman->table_exists($ann_table)) {
            $dbman->create_table($ann_table);
        }

        upgrade_mod_savepoint(true, 2026072800, 'ompdf');
    }

    return true;
}
