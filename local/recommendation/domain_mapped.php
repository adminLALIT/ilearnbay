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
$return = new moodle_url('/local/recommendation/domain_mapped.php');
// $delete = optional_param('delete', 0, PARAM_BOOL);
// $confirm = optional_param('confirm', 0, PARAM_BOOL);
// $returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot.'/local/recommendation/domain_mapped.php');
$PAGE->set_title('Domain Mapped Form');
$PAGE->set_heading('Domain Mapped Form');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/recommendation/amd/src/mapped.js');

// if ($id) {
//   $instance = $DB->get_record('company_course_domain', array('id' => $id), '*', MUST_EXIST);

//   if ($delete && $instance->id) {

//       if ($confirm && confirm_sesskey()) {
//           // Delete existing files first.
//           $DB->delete_records('company_course_domain', ['id' => $instance->id]);
//           redirect($returnurl);
//       }
//       $strheading = 'Delete Domain';
//       $PAGE->navbar->add($strheading);
//       $PAGE->set_title($strheading);
//       echo $OUTPUT->header();
//       echo $OUTPUT->heading($strheading);
//       $yesurl = new moodle_url('/local/recommendation/index.php', array(
//           'id' => $instance->id, 'delete' => 1,
//           'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl
//       ));
//       $message = "Do you really want to delete domain?";
//       echo $OUTPUT->confirm($message, $yesurl, $returnurl);
//       echo $OUTPUT->footer();
//       die;
//   }
// } else {
//   $editoroptions['subdirs'] = 0;
//   $instance = new stdClass();
//   $instance->id = null;
//   $instance->companyid = null;
// }

//Instantiate simplehtml_form 
$mform = new domain_mapped_form();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
  redirect($return);
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
//   $fromform->userid = $USER->id;
//   if ($fromform->domainid) { 
//     $fromform->id = $fromform->domainid;
//     $fromform->timemodified = time();
//     $updated = $DB->update_record('company_course_domain', $fromform);
//     if ($updated) {
//         redirect($return, 'Record Updated Successfully', null, \core\output\notification::NOTIFY_INFO);
//     }
//   }
//   else {
//     $fromform->timecreated = time();
//    $inserted =  $DB->insert_record('company_course_domain', $fromform, $returnid=true, $bulk=false);
//   }
//  if ($inserted) {
//  redirect($return, 'Record Save Successfully', null,  \core\output\notification::NOTIFY_SUCCESS);
//  }
}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

?>