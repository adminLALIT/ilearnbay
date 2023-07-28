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

require('../../config.php');

require_login();
global $DB, $USER;
$context = context_system::instance();
$meetingid = required_param('id', PARAM_INT);
$companyid = required_param('company', PARAM_INT);
$PAGE->set_context($context);
$PAGE->set_url('/local/vilt/catalog.php');
$PAGE->set_heading('Training Session Enrollment');
$PAGE->set_title('Training Session Enrollment');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/vilt/amd/src/jquery.js');
$PAGE->requires->js('/local/vilt/amd/src/domainrec.js');

echo $OUTPUT->header();
$meetingrecord = $DB->get_record('webexactivity', ['id' => $meetingid]);
$companyerecord = $DB->get_record('company', ['id' => $companyid]);
$data = [
    'sessionname' => $meetingrecord->name,
    'company' => $companyerecord->name,
    'startdate' => date('d-m-Y h:i:s a', $meetingrecord->starttime),
    'duration' => $meetingrecord->duration . " (min)",
];
echo $OUTPUT->render_from_template('local_vilt/enrolment', $data);

$viltrecord = $DB->get_record('viltrecord', ['webexid' => $meetingid]);
if ($viltrecord->meetingtype == 'openuser') {
    if ($DB->record_exists('meeting_requests', ['userid' => $USER->id, 'companyid' => $companyid, 'meetingid' => $meetingid, 'status' => 'pending'])) {
        $getrecord = $DB->get_record('meeting_requests', ['userid' => $USER->id, 'companyid' => $companyid, 'meetingid' => $meetingid, 'status' => 'pending']);
        $msg = 'Your request message has been waiting to approve. Please wait for confirmation.';
        $requesturl = 'enrol.php';
        $requestparam =  array('meetingid' => $meetingid, 'companyid' => $companyid, 'userid' => $USER->id, 'cancel' => $getrecord->id);
        $request = 'Cancel';
        $classparam = ['class' => 'btn btn-primary'];
        echo \core\notification::error($msg);
    } elseif ($DB->record_exists('meeting_requests', ['userid' => $USER->id, 'companyid' => $companyid, 'meetingid' => $meetingid, 'status' => 'approved'])) {
        $request = 'Enrolled';
        $requesturl = '#';
        $requestparam = [];
        $classparam = ['class' => 'btn btn-success', 'style' => 'pointer-events:none;'];
    } elseif ($DB->record_exists('meeting_requests', ['userid' => $USER->id, 'companyid' => $companyid, 'meetingid' => $meetingid, 'status' => 'declined'])) {
        $request = 'Declined';
        $requesturl = '#';
        $requestparam = [];
        $classparam = ['class' => 'btn btn-danger', 'style' => 'pointer-events:none;'];
    } else {
        $requesturl = 'enrol.php';
        $requestparam = array('meetingid' => $meetingid, 'companyid' => $companyid, 'userid' => $USER->id);
        $classparam = ['class' => 'btn btn-primary'];
        $request = 'Enrol';
    }
} else {
    $requesturl = 'enrol.php';
    $requestparam = array('meetingid' => $meetingid, 'companyid' => $companyid, 'userid' => $USER->id, 'type' => 'all');
    $classparam = ['class' => 'btn btn-primary'];
    $request = 'Enrol';
}
echo html_writer::start_tag('div', ['style' => 'margin: 16px;']);
$url = new moodle_url($requesturl, $requestparam);
$buttons[] = html_writer::link($url, $request, $classparam);

$url = new moodle_url('catalog.php');
$buttons[] = html_writer::link($url, 'Back', ['class' => 'btn btn-primary']);

echo implode(' ', $buttons);
echo html_writer::end_tag('div');
echo $OUTPUT->footer();
