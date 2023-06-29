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
 * @package   local_recommendation
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//include simplehtml_form.php
require_once('../../config.php');
require_once('domain_mapped_form.php');
require_login();

global $DB;
$id = optional_param('id', 0, PARAM_INT);
$domain = optional_param('domain', 0, PARAM_INT);
$return = new moodle_url('/local/recommendation/mapped_list.php');
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot.'/local/recommendation/domain_mapped.php');
$PAGE->set_title('Domain Mapped Form');
$PAGE->set_heading('Domain Mapped Form');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/recommendation/amd/src/mapped.js');

if ($id) {
  $instance = $DB->get_record('domain_mapping', array('id' => $id), '*', MUST_EXIST);

  if ($delete && $instance->id) {

      if ($confirm && confirm_sesskey()) {
          // Delete existing files first.
          $DB->delete_records('domain_mapping', ['id' => $instance->id]);
          redirect($returnurl);
      }
      $strheading = 'Delete this domain mapping';
      $PAGE->navbar->add($strheading);
      $PAGE->set_title($strheading);
      echo $OUTPUT->header();
      echo $OUTPUT->heading($strheading);
      $yesurl = new moodle_url('/local/recommendation/domain_mapped.php', array(
          'id' => $instance->id, 'delete' => 1,
          'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl
      ));
      $message = "Do you really want to delete domain mapping?";
      echo $OUTPUT->confirm($message, $yesurl, $returnurl);
      echo $OUTPUT->footer();
      die;
  }
} else {
  $instance = new stdClass();
  $instance->id = null;
}

//Instantiate simplehtml_form 
$mform = new domain_mapped_form(null, ['id' => $id, 'instance' => $instance]);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {

  redirect($return);
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
  $profilefield = $fromform->profilefield;
  $profiletext = $fromform->profiletext;

  if ($id) {
    $fromform->id = $id;
    $fromform->time_modified = time();
    if (count($profilefield) < 2) {
      $fromform->profilefield = implode(",", $profilefield[0]);
      $fromform->profiletext = $profiletext[0];
      $updated = $DB->update_record('domain_mapping', $fromform);
    }
    else {
      $fromform->userid = $USER->id;
      $fromform->domainid = $domain;
      $fromform->time_created = time();
      for ($i=1; $i < count($profilefield); $i++) { 
        $fromform->profilefield = implode(",", $profilefield[$i]);
        $fromform->profiletext = $profiletext[$i];
        $updated = $DB->insert_record('domain_mapping', $fromform, $returnid=true, $bulk=false);
      }
    }
    if ($updated) {
        redirect($return, 'Record updated Successfully', null, \core\output\notification::NOTIFY_INFO);
    }
  }
  else {
    $fromform->userid = $USER->id;
    $fromform->domainid = $domain;
    $fromform->time_created = time();

    for ($i=0; $i < count($profilefield); $i++) { 
      $fromform->profilefield = implode(",", $profilefield[$i]);
      $fromform->profiletext = $profiletext[$i];
      $inserted =  $DB->insert_record('domain_mapping', $fromform, $returnid=true, $bulk=false);
    }
  
   if ($inserted) {
   redirect($return, 'Record Save Successfully', null,  \core\output\notification::NOTIFY_SUCCESS);
   }
  }  

}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
