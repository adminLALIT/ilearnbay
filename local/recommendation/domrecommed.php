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
require_once('domainform.php');
require_once('lib.php');
require_login();

global $DB;
$return = $CFG->wwwroot.'/local/recommendation/domrecommed.php';
$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot.'/local/recommendation/domrecommed.php');
$PAGE->set_title('Domain Recommendation');
$PAGE->set_heading('Domain Recommendation');
$PAGE->requires->js('/local/recommendation/amd/src/jquery.js');
$PAGE->requires->js('/local/recommendation/amd/src/domainrec.js');
//Instantiate simplehtml_form 
$mform = new domain_assign();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {

  redirect($return);
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    $fromform->timecreated = time();
    $fromform->studentuserid = $USER->id;
    $inserted =  $DB->insert_record('additional_domains', $fromform, $returnid=true, $bulk=false);
   if ($inserted) {
   redirect($return, 'Record Save Successfully', null,  \core\output\notification::NOTIFY_SUCCESS);
   }
  }  

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
