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
require_once($CFG->libdir . '/datalib.php');
require_once('lib.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/vilt/createvilt.php');
$id = optional_param('id', 0, PARAM_INT);
$return = new moodle_url('/local/vilt/viltlist.php');
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

//Instantiate simplehtml_form 
$editoroptions = array('maxfiles' => 1, 'maxbytes' => 262144, 'trusttext' => false, 'noclean' => true, 'subdirs' => 0, 'context' => $context,);

if ($id) {

    $instance = $DB->get_record('webexactivity', array(
        'id' => $id
    ), '*', MUST_EXIST);
    $editoroptions['subdirs'] = file_area_contains_subdirs(context_system::instance(), 'mod_webexactivity', 'intro', $id);
    $course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
    $instance = file_prepare_standard_editor($instance, 'intro', $editoroptions, context_system::instance(), 'mod_webexactivity', 'intro', $instance->id);
    $instance->introeditor = $instance->intro_editor;
    $customrecord = $DB->get_record('viltrecord', ['webexid' => $id]);
    $instance->companyid = $customrecord->companyid;
    $instance->type = $customrecord->meetingtype;
    if ($delete && $instance->id) {

        if ($confirm && confirm_sesskey()) {
            // Delete existing files first.
            delete_course($course);
            fix_course_sortorder();
            $DB->delete_records('viltrecord', ['webexid' => $id]);
            redirect($returnurl);
        }
        $strheading = 'Delete meeting';
        $PAGE->navbar->add($strheading);
        $PAGE->set_title($strheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $yesurl = new moodle_url('/local/vilt/createvilt.php', array(
            'id' => $instance->id, 'delete' => 1,
            'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl
        ));
        $message = "Do you really want to delete this meeting ?";
        echo $OUTPUT->confirm($message, $yesurl, $returnurl);
        echo $OUTPUT->footer();
        die;
    }
} else {
    $PAGE->set_title('Create VILT');
    $PAGE->set_heading('Create VILT');
    $instance = new stdClass();
    $instance->id = null;
    $instance->companyid = null;
    $instance->type = null;
    $instance->course = null;
}


$mform = new \local_vilt\viltform(null, ['context' => $context, 'editoroptions' => $editoroptions, 'instance' => $instance, 'id' => $id]);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    redirect('trainingdata.php');
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    $categoryid = $DB->get_field('course_categories', 'id', ['idnumber' => 'VILT']);
    $moduledata = $DB->get_record('modules', ['name' => 'webexactivity']);
    $fromform->modulename = $moduledata->name;
    $fromform->module = $moduledata->id;
    $fromform->visible = 1;
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    if ($fromform->meetingid) {
        $coursemodule = $DB->get_record('course_modules', ['course' => $fromform->course, 'instance' => $fromform->meetingid]);
        $fromform->coursemodule = $coursemodule->id;
        $fromform->instance = $fromform->meetingid;
        // Check the course module exists.
        $cm = get_coursemodule_from_id('', $coursemodule->id, 0, false, MUST_EXIST);

        // Check the course exists.
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        list($cm, $fromform) = update_moduleinfo($cm, $fromform, $course, $mform);
        redirect('trainingdata.php', 'Record Update Successfully', null, \core\output\notification::NOTIFY_SUCCESS);

    } else {
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
      
        $fromform->visibleoncoursepage = 1;
        $fromform->course = $course->id;
        $fromform->section = 1;
        $fromform->introformat = 1;
        $fromform->section = 1;
        $fromform->add = "webexactivity";
        $fromform = add_moduleinfo($fromform, $course, $mform);

        $insertrecord = new stdClass();
        $insertrecord->courseid = $course->id;
        $insertrecord->companyid = $fromform->companyid;
        $insertrecord->webexid = $fromform->instance;
        $insertrecord->meetingtype = $meetingtype;
        $insertrecord->creatorid = $USER->id;
        $insertrecord->timecreated = time();
        $insertrecord->timemodified = time();
        $insertrecord->id = $DB->insert_record('viltrecord', $insertrecord, $returnid = true, $bulk = false);
        if ($insertrecord->id) {
            redirect('trainingdata.php', 'Record Save Successfully', null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}
echo $OUTPUT->header();

$mform->display();
echo $OUTPUT->footer();
