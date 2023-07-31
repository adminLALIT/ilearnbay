<?php

namespace local_vilt;

use html_writer;
use moodle_url;

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
    $columns = array('name', 'companyname', 'starttime', 'meetingtype', 'duration', 'action');
    $this->define_columns($columns);

    // Define the titles of columns to show in header.
    $headers = array('Meeting Name', 'Company', 'Start Time', 'Type', 'Duration', 'Action');
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
   * action value.
   *
   * @param object $values Contains object with all the values of record.
   */

  function col_action($values)
  {

    global $CFG, $DB, $OUTPUT;
    $baseurl = new moodle_url('/local/vilt/trainingdata.php');
    $url = new moodle_url('createvilt.php', array('delete' => 1, 'id' => $values->id, 'returnurl' => $baseurl));
    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'Delete'));
    $url = new moodle_url('createvilt.php', array('id' => $values->id));
    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', 'Edit'));
    if ($values->meetingtype == 'onlyinvited') {
      $url = new moodle_url('adduser.php', array('id' => $values->course, 'company' => $values->companyid));
      $buttons[] = html_writer::link($url, '<i class="fa fa-user-plus" aria-hidden="true" title="Add user"></i>');
    }
    if ($values->meetingtype == 'openuser' || $values->meetingtype == 'all' || $values->meetingtype == 'public') {
      $url = new moodle_url('request.php', array('id' => $values->course, 'company' => $values->companyid));
      $buttons[] = html_writer::link($url, '<i class="fa fa-thumbs-up fa-fw" aria-hidden="true" title="Approval"></i>');
    }
    if ($values->meetingtype == 'all') {
      $url = new moodle_url('assignprofile.php', array('id' => $values->course, 'company' => $values->companyid));
      $buttons[] = html_writer::link($url, '<i class="fa fa-user-plus" aria-hidden="true" title="Assign Profile Field"></i>');
    }
    if ($values->meetingtype == 'public') {
      $url = new moodle_url('registrationsetup.php', array('id' => $values->course, 'company' => $values->companyid));
      $buttons[] = html_writer::link($url, '<i class="fa fa-file" aria-hidden="true" title="Registration Form Setup"></i>');
      $url = new moodle_url('meetingoverview.php', array('id' => $values->course, 'company' => $values->companyid));
      $buttons[] = html_writer::link($url, '<i class="fa fa-list-alt" aria-hidden="true" title="Meeting Overview Page"></i>');
      
    }
    $url = new moodle_url('/course/view.php', array('id' => $values->course));
    $buttons[] = html_writer::link($url, '<i class="fa fa-meetup" aria-hidden="true" title="View Meeting"></i>
    ');

    
    return implode(' ', $buttons);
  }
}
