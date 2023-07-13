<?php

function xmldb_local_recommendation_upgrade($oldversion): bool
{
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2023030915) {
        // Perform the upgrade from version 2023051103 to the next version.

        // The content of this section should be generated using the XMLDB Editor.

        $table = new xmldb_table('domain_mapping');

        // Add columns.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('domainid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('profiletext', XMLDB_TYPE_CHAR, '200', null, null);
        $table->add_field('profilefield', XMLDB_TYPE_CHAR, '200', null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('time_modified', XMLDB_TYPE_INTEGER, '20', null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        
        // The content of this section should be generated using the XMLDB Editor.

        $table = new xmldb_table('curator_assign_course');

        // Add columns.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('domain', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('content', XMLDB_TYPE_CHAR, '250', null, null);
        $table->add_field('curatoruserid', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('time_modified', XMLDB_TYPE_INTEGER, '20', null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    
        $table = new xmldb_table('curator_save_content');

        // Add columns.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('curatorid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('videoid', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('videolink', XMLDB_TYPE_TEXT, '200', null, null);
        $table->add_field('contenttype', XMLDB_TYPE_CHAR, '100', null, null);
        $table->add_field('domain', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '20', null, null);
        $table->add_field('time_modified', XMLDB_TYPE_INTEGER, '20', null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('additional_domains');

        // Add columns.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('studentuserid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);
        $table->add_field('domainid', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Create the table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    }

    if ($oldversion < 2023051103) {
        // Perform the upgrade from version 2023051103 to the next version.

        // The content of this section should be generated using the XMLDB Editor.
    }

    // Everything has succeeded to here. Return true.
    return true;
}
