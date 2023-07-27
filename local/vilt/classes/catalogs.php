<?php

namespace local_vilt;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * team_list_form class to be put in team_list_form.php of root of Moodle installation.
 *  for defining some custom column names and proccessing.
 */
class catalogs extends \table_sql
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
    $columns = array('name', 'companyname', 'starttime', 'meetingtype', 'duration', 'status');
    $this->define_columns($columns);

    // Define the titles of columns to show in header.
    $headers = array('Meeting Name', 'Company', 'Start Time', 'Type', 'Duration', 'Status');
    $this->define_headers($headers);
  }

  /**
   * This function is called for each data row to allow processing of the
   * starttime value.
   *
   * @param object $values Contains object with all the values of record.
   */

  function col_meetingtype($values)
  {
    $meetingtype = getmeetingtype();

    return ($meetingtype[$values->meetingtype]);
  }


  /**
   * This function is called for each data row to allow processing of the
   * starttime value.
   *
   * @param object $values Contains object with all the values of record.
   */

  function col_starttime($values)
  {
    return (date('Y-m-d H:i:s a', $values->starttime));
  }

  /**
   * This function is called for each data row to allow processing of the
   * status value.
   *
   * @param object $values Contains object with all the values of record.
   */

  function col_status($values)
  {

    $url = new moodle_url('sessionenrolment.php', array('id' => $values->id, 'company' => $values->companyid));
    $buttons[] = html_writer::link($url, 'Sign Up');

    return implode(' ', $buttons);
  }
}
