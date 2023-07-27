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
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    local_vilt
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_login();
global $DB, $USER;
$context = context_system::instance();
$PAGE->set_context($context);    
$meetingid = optional_param('meetingid', 0, PARAM_INT);
$companyid = optional_param('companyid', 0, PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$cancel = optional_param('cancel', 0,  PARAM_INT);
$courseid = optional_param('courseid', 0,  PARAM_INT);
$requestid = optional_param('requestid', 0,  PARAM_INT);
$enrol = optional_param('enrol', false,  PARAM_BOOL);
$decline = optional_param('decline', false,  PARAM_BOOL);

$meeting = $DB->get_record('webexactivity', ['id' => $meetingid]);  // Get meeting information.

if ($cancel) {
   // If user click on cancel button.
   $DB->delete_records('meeting_requests', ['id' => $cancel]);
   redirect('sessionenrolment.php?id='.$meetingid.'&company='.$companyid.'', 'Your enrollment request cancelled successfully.', null, \core\output\notification::NOTIFY_INFO);
}
elseif ($enrol) {
    $instances = $DB->get_records_sql("SELECT * FROM {enrol} WHERE courseid = " . $courseid . " AND status = 0 AND enrol = 'manual'");
    foreach ($instances as $instance) {
        $enrolmethod = 'manual';
        $roleid = 5;
        $plugin = enrol_get_plugin($instance->enrol);
        $now = time();
        $timestart = intval(substr($now, 0, 8) . '00') - 1;
        $timeend = 0;
        $result = $plugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
        
    }

    // Status need to update after enrollment.
    $updaterecord = new stdClass();
    $updaterecord->id = $requestid;   // Requestid of request.
    $updaterecord->status = 'approved';
    $updaterecord->approverid = $USER->id;
    $updaterecord->timemodified = time();
    $DB->update_record('meeting_requests', $updaterecord, $bulk=false);

    // Send notification.
    $user = $DB->get_record('user', ['id' => $userid]);
    $course = $DB->get_record('course', ['id' => $courseid]);
    $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
    $a = new stdClass();
    $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
    $eventdatareceipt = new \core\message\message();
    $eventdatareceipt->courseid          = $courseid;
    $eventdatareceipt->modulename        = 'moodle';
    $eventdatareceipt->component         = 'local_vilt';
    $eventdatareceipt->name              = 'meeting_enrolment';
    $eventdatareceipt->userfrom          = core_user::get_noreply_user();
    $eventdatareceipt->userto            = $user;
    $eventdatareceipt->subject           = get_string("newenrolment", 'local_vilt');
    $eventdatareceipt->fullmessage       = get_string('welcometocoursetext', 'local_vilt', $a);
    $eventdatareceipt->fullmessageformat = FORMAT_PLAIN;
    $eventdatareceipt->fullmessagehtml = '';
    $eventdatareceipt->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
    $eventdatareceipt->smallmessage      = '';
    message_send($eventdatareceipt);

    redirect('request.php?id='.$courseid.'');

}
elseif ($decline) {
    
    // Status need to update after enrollment.
    $updaterecord = new stdClass();
    $updaterecord->id = $requestid;   // Requestid of request.
    $updaterecord->status = 'declined'; 
    $updaterecord->approverid = $USER->id;
    $updaterecord->timemodified = time();
    $DB->update_record('meeting_requests', $updaterecord, $bulk=false);
    redirect('request.php?id='.$courseid.'');

}
else {
 // If user click on enrol button.
    $data = new stdClass();
    $data->userid = $userid;
    $data->companyid = $companyid;
    $data->courseid = $meeting->course;
    $data->status = 'pending';
    $data->meetingid = $meetingid;
    $data->timecreated = time();
    
    $insert = $DB->insert_record('meeting_requests', $data, $returnid = true, $bulk = false);
    if ($insert) {
        redirect('sessionenrolment.php?id='.$meetingid.'&company='.$companyid.'', 'Your enrollment request sent successfully.', null, \core\output\notification::NOTIFY_INFO);
    }
}