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
require "$CFG->libdir/tablelib.php";
require_once('lib.php');
require_login();

$companymanagerrole = $DB->get_field('role', 'id', ['shortname' => 'companymanager']);
$companyid = '';
if (!is_siteadmin()) {
    if (!user_has_role_assignment($USER->id, $companymanagerrole)) {
        throw new moodle_exception(get_string('nopermission', 'local_vilt', 'core'));
    }
   else {
    $companyid = $DB->get_record('company_users', ['userid' => $USER->id]);
   }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/vilt/trainingdata.php');
$PAGE->set_title('Manage VILT Training');
$PAGE->set_heading('Manage VILT Training');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
echo html_writer::start_tag('div', ['style' => 'float:right']);
echo $OUTPUT->single_button($CFG->wwwroot . '/local/vilt/createvilt.php', 'Create VILT');
echo html_writer::end_tag('div');
echo '<br><br>';

$table = new \local_vilt\viltlist('uniqueid');
$mform = new \local_vilt\filterform();
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    redirect('trainingdata.php');
} else if ($fromform = $mform->get_data()) {
    if (!empty($fromform->companyid)) {
        $where = 'c.id = ' . $fromform->companyid;
    } else {
        $where = '1=1';
    }
} else {
    if (!empty($companyid)) {
       $where = 'c.id = ' . $companyid->companyid;
    }
    else {
        $where = '1=1';
    }
}

$field = 'wa.*, c.name as companyname, vr.meetingtype';
$from = '{webexactivity} wa JOIN {viltrecord} vr LEFT JOIN {company} c ON c.id = vr.companyid';
// Work out the sql for the table.
$table->set_sql($field, $from, $where);
$table->no_sorting('companyname');
$table->no_sorting('action');
$table->define_baseurl("$CFG->wwwroot/local/vilt/trainingdata.php");
if (empty($companyid)) {
    $mform->display();
}
$table->out(10, true);
echo $OUTPUT->footer();
