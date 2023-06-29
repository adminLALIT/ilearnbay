<?php

/**
 * team_list_form class to be put in team_list_form.php of root of Moodle installation.
 *  for defining some custom column names and proccessing.
 */
class assign_course_list extends table_sql
{
    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array('name', 'coursename', 'domain', 'content', 'username', 'action');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array('Company', 'Course', 'Domain', 'Content', 'Curator', 'Action');
        $this->define_headers($headers);
    }
 
    /**
     * This function is called for each data row to allow processing of the
     * action value.
     *
     * @param object $values Contains object with all the values of record.
     */

    function col_action($values)
    {
        
        global $CFG, $DB, $OUTPUT;
        $baseurl = new moodle_url('/local/recommendation/assign_course_list.php');
        $url = new moodle_url('assign_course.php', array('delete' => 1, 'id' => $values->id, 'returnurl' => $baseurl));
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'Delete'));
        $url = new moodle_url('assign_course.php', array('id' => $values->id));
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', 'Edit'));

        return implode(' ', $buttons);
    }
}
