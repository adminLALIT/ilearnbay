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
require_once('lib.php');
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
$PAGE->set_url($CFG->wwwroot . '/local/vilt/registration.php', ['id' => $courseid, 'company' => $companyid]);
$PAGE->set_title('Registration Form Setup');
$PAGE->set_heading('Registration Form Setup');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/vilt/amd/src/mapped.js');
$meeting = $DB->get_record('webexactivity', ['course' => $courseid]);

if ($courseid) {
    $instance = $DB->get_record('registration_fields', ['course' => $courseid]);
} else {
    $instance = new stdClass();
    $instance->course  = $courseid;
    $instance->id  = null;
}
$mform = new \local_vilt\registrationfields($CFG->wwwroot . '/local/vilt/registrationsetup.php?id=' . $courseid . '&company=' . $companyid . '', ['id' => $meeting->id, 'instance' => $instance]);
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    redirect('trainingdata.php');
} else if ($fromform = $mform->get_data()) {
    $profilefields = implode(",", $fromform->profilefields);
    $fromform->profilefields = $profilefields;
    $fromform->timemodified = time();
    if (!empty($fromform->formid)) {
        $fromform->id = $fromform->formid;
        $DB->update_record('registration_fields', $fromform, $bulk=false);
        redirect('trainingdata.php', 'Records updated successfully', null, \core\output\notification::NOTIFY_SUCCESS);

    }
    else {
        $fromform->creatorid = $USER->id;
        $fromform->timecreated = time();
        $inserted = $DB->insert_record('registration_fields', $fromform, $returnid = true, $bulk = false);
        if ($inserted)
            redirect('trainingdata.php', 'Records saved successfully', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
