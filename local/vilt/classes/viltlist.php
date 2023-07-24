<?php
namespace local_vilt;
defined('MOODLE_INTERNAL') || die();

/**
 * team_list_form class to be put in team_list_form.php of root of Moodle installation.
 *  for defining some custom column names and proccessing.
 */
class viltlist extends \table_sql
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
    $columns = array('name', 'starttime', 'duration', 'action');
    $this->define_columns($columns);

    // Define the titles of columns to show in header.
    $headers = array('Meeting Name', 'Start Time', 'Duration', 'Action');
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
    $baseurl = $CFG->wwwroot.'/local/vilt/trainingdata.php';
    $url = $CFG->wwwroot.'createvilt.php?delete=1&id='.$values->id.'&returnurl='.$baseurl;
    // $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'Delete'));
    $url = $CFG->wwwroot.'createvilt.php?id='.$values->id.'';
    // $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', 'Edit'));

    // return implode(' ', $buttons);
  }
}
