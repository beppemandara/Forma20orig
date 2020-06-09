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
 * This file keeps track of upgrades to the f2_formazione_individuale block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @since 2.0
 * @package blocks
 * @copyright 2016 Aktive Reply S.r.l.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_f2_formazione_individuale_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012121702) {

        // Define table f2_fi_partialbudget to be created
        $table = new xmldb_table('f2_fi_partialbudget');
        
        // Define fields to be added to f2_fi_partialbudget
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('anno', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('orgfk', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'anno');
        $table->add_field('tipo', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'orgfk');
        $table->add_field('money_bdgt', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, null, 'tipo');
        $table->add_field('lstupd', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'money_bdgt');
        $table->add_field('usrname', XMLDB_TYPE_CHAR, '90', null, XMLDB_NOTNULL, null, null, 'lstupd');

        // Define keys to be added to f2_fi_partialbudget
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Define key mdl_f2orgbudg_annorgtip_uix (unique) to be added to f2_fi_partialbudget
        $table->add_key('mdl_f2orgbudg_annorgtip_uix', XMLDB_KEY_UNIQUE, array('anno', 'orgfk', 'tipo'));
        
        // Conditionally launch create table for block_community
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // community savepoint reached
        upgrade_block_savepoint(true, 2012121702, 'f2_formazione_individuale');
    }

    return true;
}