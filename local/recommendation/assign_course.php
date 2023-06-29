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
require_once('assign_course_form.php');
require_login();

global $DB;
$id = optional_param('id', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
$domain = optional_param('domain', 0, PARAM_INT);
$return = new moodle_url('/local/recommendation/assign_course_list.php');
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/recommendation/assign_course.php');
$PAGE->set_title('Assign Course Form');
$PAGE->set_heading('Assign Course Form');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/recommendation/amd/src/mapped.js');


if ($id) {
    $instance = $DB->get_record('curator_assign_course', array('id' => $id), '*', MUST_EXIST);

    if ($delete && $instance->id) {

        if ($confirm && confirm_sesskey()) {
            // Delete existing files first.
            $DB->delete_records('curator_assign_course', ['id' => $instance->id]);
            redirect($returnurl);
        }
        $strheading = 'Delete this assignment';
        $PAGE->navbar->add($strheading);
        $PAGE->set_title($strheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $yesurl = new moodle_url('/local/recommendation/assign_course.php', array(
            'id' => $instance->id, 'delete' => 1,
            'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl
        ));
        $message = "Do you really want to delete assign?";
        echo $OUTPUT->confirm($message, $yesurl, $returnurl);
        echo $OUTPUT->footer();
        die;
    }
} else {
  $instance = new stdClass();
  $instance->id = null;
}

//Instantiate simplehtml_form 
$mform = new assign_course_form(null, ['id' => $id, 'instance' => $instance]);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {

  redirect($return);
  //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
  $fromform->course = $course;
  $fromform->domain = $domain;
  $fromform->userid = $USER->id;
  $fromform->content = implode(",", $fromform->content);

  if ($id) {
    // $fromform->id = $id;
    // $fromform->time_modified = time();
    // if (count($profilefield) < 2) {
    //   $fromform->profiletext = $profiletext[0];
    //   $updated = $DB->update_record('curator_assign_course', $fromform);
    // }
    // else {
    //   $fromform->userid = $USER->id;
    //   $fromform->domainid = $domain;
    //   $fromform->time_created = time();
    //   for ($i=1; $i < count($profilefield); $i++) { 
    //     $fromform->profilefield = implode(",", $profilefield[$i]);
    //     $fromform->profiletext = $profiletext[$i];
    //     $updated = $DB->insert_record('curator_assign_course', $fromform, $returnid=true, $bulk=false);
    //   }
    // }
    // if ($updated) {
    //     redirect($return, 'Record updated Successfully', null, \core\output\notification::NOTIFY_INFO);
    // }
  } else {
    $fromform->time_created = time();

    $inserted =  $DB->insert_record('curator_assign_course', $fromform, $returnid = true, $bulk = false);

    if ($inserted) {
      redirect($return, 'Record Save Successfully', null,  \core\output\notification::NOTIFY_SUCCESS);
    }
  }
}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
