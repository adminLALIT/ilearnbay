<?php

function xmldb_local_vilt_upgrade($oldversion): bool
{
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2023030916) {
        // Perform the upgrade from version 2023051103 to the next version.

        // The content of this section should be generated using the XMLDB Editor.

        $table = new xmldb_table('meeting_requests');

        // Add columns.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '200', null, null);
        $table->add_field('meetingid', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('approverid', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // The content of this section should be generated using the XMLDB Editor.

        $table = new xmldb_table('profilemapping');

        // Add columns.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('meetingid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('type', XMLDB_TYPE_CHAR, '200', null, null);
        $table->add_field('profileid', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('profilevalue', XMLDB_TYPE_TEXT, '200', null, null);
        $table->add_field('creatorid', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table2 = new xmldb_table('registration_fields');
        if (!$dbman->table_exists($table2)) {
            $dbman->install_one_table_from_xmldb_file(__DIR__.'/install.xml', 'registration_fields');
        }

        $table3 = new xmldb_table('timer');
        if (!$dbman->table_exists($table3)) {
            $dbman->install_one_table_from_xmldb_file(__DIR__.'/install.xml', 'timer');
        }

        upgrade_plugin_savepoint(true, '2023030916', 'local', 'vilt');

    }

    if ($oldversion < 2023051103) {
        // Perform the upgrade from version 2023051103 to the next version.

        // The content of this section should be generated using the XMLDB Editor.
    }

    // Everything has succeeded to here. Return true.
    return true;
}
