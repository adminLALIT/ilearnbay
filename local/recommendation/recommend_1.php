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
 * @package   local_recommendation
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('course_content_form.php');

require_login();
$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$return = $CFG->wwwroot.'/local/recommendation/recommend.php';
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/local/recommendation/domain_mapped.php');
$PAGE->set_title('Course Recommendation');
$PAGE->set_heading('Course Recommendation');
$PAGE->set_pagelayout('admin');
$editoroptions = array(
    'maxfiles' => 1,
    'maxbytes' => 262144, 'subdirs' => 0, 'context' => $context, 'enable_filemanagement' => true, 'trusttext'=>false, 'noclean'=>true
);

//Instantiate simplehtml_form 
$mform = new course_content(null, ['id'=> $id, 'editoroption' => $editoroptions]);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {

  redirect($return);
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {


//     $inserted =  $DB->insert_record('domain_mapping', $fromform, $returnid=true, $bulk=false);
//    if ($inserted) {
//    redirect($return, 'Record Save Successfully', null,  \core\output\notification::NOTIFY_SUCCESS);
//    }
  }  


echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
?>