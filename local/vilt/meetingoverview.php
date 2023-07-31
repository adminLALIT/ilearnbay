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

$courseid = optional_param('id', 0, PARAM_INT);
$companyid = optional_param('company', 0, PARAM_INT);
$companymanagerrole = $DB->get_field('role', 'id', ['shortname' => 'companymanager']);

if (!is_siteadmin()) {
    if (!user_has_role_assignment($USER->id, $companymanagerrole)) {
        throw new moodle_exception(get_string('nopermission', 'local_vilt', 'core'));
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/vilt/meetingoverview.php', ['id' => $courseid, 'company' => $companyid]);
$PAGE->set_title('Meeting Overview');
$PAGE->set_heading('Meeting Overview');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/vilt/amd/src/copy.js');

echo $OUTPUT->header();
echo $OUTPUT->single_button('trainingdata.php', 'Back');
$meetingrecord = $DB->get_record_sql("SELECT w.*, c.fullname, cc.name as categoryname FROM {webexactivity} w JOIN {course} c ON c.id = w.course JOIN {course_categories} cc ON cc.id = c.category WHERE course = '$courseid'");
$formurl              = new moodle_url('/local/vilt/registrationform.php', array(
    'meetingid' => $meetingrecord->id
));

echo html_writer::div('
<table width="100%" class="table table-borderless table-hover">
  <thead class="">
    <tr>
    <th colspan="6" scope="col" class="heading-bg" style="background:#5d63f6; color:white;"><span class="graduation"></span> ' . $meetingrecord->name . ' Information</th>
      </tr>
  </thead>
  <tbody>
    <tr>
      <td width="13%"><strong>' . get_string('category', 'local_vilt') . '</strong></td>
      <td width="29%">' . $meetingrecord->categoryname . '</td>
      <td width="12%"><strong>' . get_string('course', 'local_vilt') . '</strong></td>
      <td width="21%">' . $meetingrecord->fullname . '</td>
      <td width="10%"><strong>' . get_string('modulename', 'local_vilt') . '</strong></td>
      <td width="15%">' . get_string('webexactivity', 'local_vilt') . '</td>
      </tr>
    <tr>
      <td><strong>' . get_string('meetingname', 'local_vilt') . '</strong></td>
      <td>' . $meetingrecord->name . '</td>
      <td><strong>' . get_string('meetingstarttime', 'local_vilt') . '</strong></td>
      <td>' . date('d-m-Y h:i:s a', $meetingrecord->starttime) . '</td>
      <td><strong>' . get_string('meetingduration', 'local_vilt') . '</strong></td>
      <td>' . $meetingrecord->duration . ' (min)</td>
      </tr>
    <tr>
      <td><strong>' . get_string('assignedusers', 'local_vilt') . '</strong></td>
      <td>10</td>
      <td><strong>' . get_string('registeredusers', 'local_vilt') . '</strong></td>
      <td>5</td>
      </tr>
    <tr>
      <td><strong>' . get_string('formlink', 'local_vilt') . '</strong></td>
      <td><a href='.$formurl.' target="_blank">Registration Link </a> <span id="copylink" onclick="copyAddress()" data="'.$formurl.'" style="cursor: copy;">' . $OUTPUT->pix_icon('t/copy', get_string('copy')) . '</span></td>
    
    </tr>
   </tbody>
</table>
</div>', 'container-fluid table-responsive py-1 userdetails');

echo $OUTPUT->footer();
?>
