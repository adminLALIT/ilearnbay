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
require("$CFG->libdir/tablelib.php");

require_login();
$companymanagerrole = $DB->get_field('role', 'id', ['shortname' => 'companymanager']);
if (!is_siteadmin()) {
    if (!user_has_role_assignment($USER->id, $companymanagerrole)) {
        throw new moodle_exception(get_string('nopermission', 'local_vilt', 'core'));
    }
}
global $DB, $USER;
$context = context_system::instance();
$courseid = required_param('id', PARAM_INT);
$PAGE->set_context($context);
$PAGE->set_url('/local/vilt/request.php');
$PAGE->set_heading('Approval Request');
$PAGE->set_title('Approval Request');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
$requests = $DB->get_records('meeting_requests', ['courseid' => $courseid]);
$url = new moodle_url('/local/vilt/request.php', ['id' => $courseid]);
$waitinglist = [];
$approvedlist = [];
$declinedlist = [];

if ($requests) {
    foreach ($requests as $requestvalue) {
        if ($requestvalue->status == 'pending') {
            $waitinglist[] = $requestvalue->id;
        }
        if ($requestvalue->status == 'approved') {
            $approvedlist[] = $requestvalue->id;
        }
        if ($requestvalue->status == 'declined') {
            $declinedlist[] = $requestvalue->id;
        }
    }
}

echo $OUTPUT->single_button('trainingdata.php', 'Back');
echo html_writer::tag('p', 'Approved Users ('.count($approvedlist).')', ['style' => 'color:green;']);
echo html_writer::tag('p', 'Waitinglist Users ('.count($waitinglist).')');
echo html_writer::tag('p', 'Declined Users ('.count($declinedlist).')', ['style' => 'color:red;']);

$table = new \local_vilt\requestlist('uniqueid');
$where = 'courseid='.$courseid;
$field = 'u.*, mr.courseid, mr.status, mr.id as requestid';
$from = '{user} u JOIN {meeting_requests} mr ON mr.userid = u.id';
// Work out the sql for the table.
$table->set_sql($field, $from, $where);
$table->no_sorting('companyname');
$table->no_sorting('action');
$table->define_baseurl($url);

$table->out(10, true);

echo $OUTPUT->footer();
