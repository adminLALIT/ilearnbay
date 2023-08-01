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

$userid = optional_param('userid', 0, PARAM_INT);
$enrol = optional_param('enrol', false, PARAM_BOOL);
$requestid = optional_param('requestid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($DB->record_exists('meeting_requests', ['id' => $requestid])) {
    $requeststatus = $DB->get_record('meeting_requests', ['id' => $requestid]);
}
$companymanagerrole = $DB->get_field('role', 'id', ['shortname' => 'companymanager']);

if (!is_siteadmin()) {
    if (!user_has_role_assignment($USER->id, $companymanagerrole)) {
        throw new moodle_exception(get_string('nopermission', 'local_vilt', 'core'));
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/vilt/viewdetail.php', ['userid' => $userid, 'enrol' => $enrol, 'requestid' => $requestid]);
$PAGE->set_title('User Detail');
$PAGE->set_heading('User Detail');
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->single_button('request.php?id=' . $courseid . '', 'Back');

$customfields = $DB->get_field('registration_fields', 'profilefields', ['course' => $courseid]);
$meetingname = $DB->get_field('webexactivity', 'name', ['course' => $courseid]);
if ($customfields) {
    $profilefields = $DB->get_records_sql("SELECT * FROM {user_info_field} WHERE id IN ($customfields)");
    $html = '';
    $html .= '
<table width="100%" class="table table-borderless table-hover" style=" border: 1px solid gainsboro;
margin-bottom: 1rem;
color: #1d2125;">
  <thead class="">
    <tr>
    <th colspan="6" scope="col" class="heading-bg" style="background:#5d63f6; color:white;"><span class="graduation"></span> ' . $meetingname . ' Information</th>
      </tr>
  </thead>
  <tbody>';
    $html .= '<tr>';
    $count = 1;
    foreach ($profilefields as $profilefield) {
        $userrecord = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $profilefield->id]);
        if (!empty($userrecord->data)) {
            $html .= '
            <td width="13%"><strong>' . $profilefield->name . '</strong></td>
            <td width="29%">' . $userrecord->data . '</td>
            ';
            if ($count == 3) {
                $html .= '</tr><tr>';
                $count = 0;
            }

            $count++;
        }
    }
    $html .= '</tr></tbody>
</table>
</div>';
}

echo html_writer::div($html, 'container-fluid table-responsive py-1 userdetails');
echo html_writer::start_tag('div', ['id' => 'viewdetail', 'style' => 'display: flex;
justify-content: center;']);
if ($requeststatus) {
    if ($requeststatus->status == 'pending') {
        $url = new moodle_url('enrol.php', array('userid' => $userid, 'courseid' => $courseid, 'enrol' => true, 'requestid' => $requestid));
        echo html_writer::link($url, 'Approve', array('class' => 'btn btn-success'));
        $url = new moodle_url('enrol.php', array('userid' => $userid, 'courseid' => $courseid, 'decline' => true, 'requestid' => $requestid));
        echo html_writer::link($url, 'Decline', array('class' => 'btn btn-danger', 'style' => 'margin: 0 9px;'));
    }
    if ($requeststatus->status == 'approved') {
        echo html_writer::link('#', 'Approved', array('class' => 'btn btn-success', 'style' => 'pointer-events:none;margin: 0 9px;'));
    }
    if ($requeststatus->status == 'declined') {
        echo html_writer::link('#', 'Decline', array('class' => 'btn btn-danger', 'style' => ' margin: 0 9px;pointer-events:none;'));
    }

    echo html_writer::link('request.php?id=' . $courseid . '', 'cancel', array('class' => 'btn btn-secondary'));
}
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
