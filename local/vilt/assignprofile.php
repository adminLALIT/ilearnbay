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
 * @package   local_vilt
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

$courseid = required_param('id', PARAM_INT);
$companyid = optional_param('company', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/vilt/assignprofile.php');
$PAGE->set_title('Assign Profile Field');
$PAGE->set_heading('Assign Profile Field');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/vilt/amd/src/mapped.js');

//Instantiate simplehtml_form 
$meeting = $DB->get_record('webexactivity', ['course' => $courseid]);
if ($DB->record_exists('profilemapping', ['meetingid' => $meeting->id])) {
    // $instance = $DB->get_record('profilemapping', ['meetingid' => $meeting->id]);
    $allrecord = $DB->get_records('profilemapping', ['meetingid' => $meeting->id]);
    $instance = new stdClass(); 
    $instance->profile = [];
    $instance->profilevalue = [];
    $instance->repeatelement = count($allrecord);
    $i = 0;
    foreach ($allrecord as $allvalue) {
        $instance->profile[$i] = $allvalue->profileid;
        $instance->profilevalue[$i] = $allvalue->profilevalue;
        $i++;
    }
} else {
    $instance = new stdClass();
    $instance->id = null;
    $instance->repeatelement = 1;
}


$mform = new \local_vilt\assign_profile_form($CFG->wwwroot.'/local/vilt/assignprofile.php?id='.$courseid.'&company='.$companyid.'', array('courseid' => $courseid, 'companyid' => $companyid, 'instance' => $instance));

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    redirect('trainingdata.php');
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    $meeting = $DB->get_record('webexactivity', ['course' => $fromform->courseid]);

    if($check = $DB->get_records('profilemapping',['companyid' => $fromform->companyid, 'meetingid' => $meeting->id])){
		$DB->delete_records('profilemapping', array('companyid' => $fromform->companyid, 'meetingid' => $meeting->id));
	}
    $totaldata = $fromform->profile_repeats;
    $data = new stdClass();
    $data->meetingid = $meeting->id;
    $data->companyid = $fromform->companyid;
    $data->courseid = $fromform->courseid;
    $data->type = 'all';
    $data->creatorid = $USER->id;
    $data->timecreated = time();
    $data->timemodified = time();
    for ($i = 0; $i < $totaldata; $i++) {
        $data->profileid = $fromform->profile[$i];
        $data->profilevalue = $fromform->profilevalue[$i];
        $DB->insert_record('profilemapping', $data, $returnid = true, $bulk = false);
    }

    redirect('trainingdata.php', 'Fields Assigned Successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
    //In this case you process validated data. $mform->get_data() returns data posted in form.
}
echo $OUTPUT->header();

$mform->display();
echo $OUTPUT->footer();
