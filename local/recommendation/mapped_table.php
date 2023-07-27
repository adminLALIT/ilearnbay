<?php

/**
 * team_list_form class to be put in team_list_form.php of root of Moodle installation.
 *  for defining some custom column names and proccessing.
 */
class mapped_list extends table_sql
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
        $columns = array('name', 'domain', 'profilefield', 'profiletext', 'action');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array('Company', 'Domain', 'ProfileField', 'Profilevalue', 'Action');
        $this->define_headers($headers);
    }
    /**
     * This function is called for each data row to allow processing of the
     * profilefield value.
     *
     * @param object $values Contains object with all the values of record.
     */
    function col_profilefield($values)
    {
        global $DB;
        $fieldids = explode(',', $values->profilefield);
        $fieldids = array_map('intval', $fieldids); // Convert the values to integers
        list($insql, $inparams) = $DB->get_in_or_equal($fieldids);
        $sql = "SELECT name FROM {user_info_field} WHERE id $insql";
        $profilefields = $DB->get_records_sql($sql, $inparams);
        $profilename = [];
        foreach($profilefields as $profile){
            $profilename[] = $profile->name;
        }

        return implode(", ", $profilename);
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
        $baseurl = new moodle_url('/local/recommendation/mapped_list.php');
        $url = new moodle_url('domain_mapped.php', array('delete' => 1, 'id' => $values->id, 'returnurl' => $baseurl));
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'Delete'));
        $url = new moodle_url('domain_mapped.php', array('id' => $values->id));
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', 'Edit'));

        return implode(' ', $buttons);
    }
}
