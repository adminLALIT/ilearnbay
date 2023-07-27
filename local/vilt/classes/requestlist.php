<?php

namespace local_vilt;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * team_list_form class to be put in team_list_form.php of root of Moodle installation.
 *  for defining some custom column names and proccessing.
 */
class requestlist extends \table_sql
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
    $columns = array('username', 'firstname', 'lastname', 'email', 'status');
    $this->define_columns($columns);

    // Define the titles of columns to show in header.
    $headers = array('Username', 'Firstname', 'Surname', 'Email Address', 'Status');
    $this->define_headers($headers);
  }

 

  /**
   * This function is called for each data row to allow processing of the
   * status value.
   *
   * @param object $values Contains object with all the values of record.
   */

  function col_status($values)
  {

    if ($values->status == 'pending') {
      $url = new moodle_url('enrol.php', array('userid' => $values->id, 'courseid' => $values->courseid, 'enrol' => true, 'requestid' => $values->requestid));
      $buttons[] = html_writer::link($url, 'Approve', ['class' => 'btn btn-primary']);
      $url = new moodle_url('enrol.php', array('userid' => $values->id, 'courseid' => $values->courseid, 'decline' => true, 'requestid' => $values->requestid));
      $buttons[] = html_writer::link($url, 'Decline', ['class' => 'btn btn-danger']);
    }
    if ($values->status == 'approved') {
      $buttons[] = html_writer::link('#', 'Approved', ['style' => 'pointer-events:none;', 'class' => 'btn btn-success']);
    }
    if ($values->status == 'declined') {
      $buttons[] = html_writer::link('#', 'Declined', ['style' => 'pointer-events:none;', 'class' => 'btn btn-danger']);
    }

    return implode(' ', $buttons);
  }
}
