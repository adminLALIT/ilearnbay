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
 * Bulk course registration script from a comma separated file.
 *
 * @package    local_vilt
 * @copyright  2011 Piers Harding
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');


require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/vilt/createvilt.php');
$PAGE->set_title('Create VILT');
$PAGE->set_heading('Create VILT');

//Instantiate simplehtml_form 
$editoroptions = array('maxfiles' => 1, 'maxbytes' => 262144, 'trusttext' => false, 'noclean' => true, 'subdirs' => 0, 'context' => $context,);
$mform = new \local_vilt\viltform(null, ['context' => $context, 'editoroptions' => $editoroptions]);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    redirect('trainingdata.php');
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
   
    $categoryid = $DB->get_field('course_categories', 'id', ['idnumber' => 'VILT']);
    $moduledata = $DB->get_record('modules', ['name' => 'webexactivity']);
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    $data = new stdClass();
    $data->shortname = $fromform->name;
    $data->fullname = $fromform->name;
    $data->category = $categoryid;
    $data->visible = 1;
    $data->startdate = $fromform->starttime;
    $data->summary_format = 1;
    $data->format = "singleactivity";
    $data->activitytype = "webexactivity";
    $course = create_course($data, $editoroptions);

    $meetingtype = $fromform->type;

    $fromform->password = '';
    $fromform->type = 1;
    $fromform->studentdownload = 1;
    $fromform->calpublish = 1;
    $fromform->visible = 1;
    $fromform->visibleoncoursepage = 1;
    $fromform->course = $course->id;
    $fromform->section = 1;
    $fromform->module = $moduledata->id;
    $fromform->modulename = $moduledata->name;
    $fromform->section = 1;
    $fromform->add = "webexactivity";
    $fromform = add_moduleinfo($fromform, $course, $mform);
    
    $insertrecord = new stdClass();
    $insertrecord->courseid = $course->id;
    $insertrecord->webexid = $fromform->instance;
    $insertrecord->meetingtype = $meetingtype;
    $insertrecord->creatorid = $USER->id;
    $insertrecord->timecreated = time();
    $insertrecord->timemodified = time();
    $insertrecord->id = $DB->insert_record('viltrecord', $insertrecord, $returnid=true, $bulk=false);
    if ($insertrecord->id) {
        redirect('trainingdata.php', 'Record Save Successfully', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}
echo $OUTPUT->header();

$mform->display();
echo $OUTPUT->footer();
