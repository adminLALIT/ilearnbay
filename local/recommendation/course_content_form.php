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
 * Run the code checker from the web.
 *
 * @package    local_recommendation
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class course_content extends moodleform
{
    // Add elements to form
    public function definition()
    {
        global $CFG, $USER, $DB;

        $mform = $this->_form; // Don't forget the underscore! 
        $id = $this->_customdata['id'];
        $editoroption = $this->_customdata['editoroption'];
        // $instance = $this->_customdata['instance'];

        $curatorrole = $DB->get_field('role', 'id', ['shortname' => 'curator']);
        $context = context_system::instance();
        $curatoruser = get_role_users($curatorrole, $context);

        // foreach($curatoruser as $userid => $name){
        //     $curators[$userid] = fullname($name);
        // }

        $courseinitial = ['' => 'Select Course'];
        $courses = $DB->get_records_sql_menu("SELECT id, fullname FROM {course}  ORDER BY id desc");
        $courseinitial = $courseinitial + $courses;

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('select', 'course', get_string('selectcourse', 'local_recommendation'), $courseinitial);

        $mform->addElement('editor', 'coursecontent', get_string('selectcontent', 'local_recommendation'), null,  $editoroption);
        $mform->setType('coursecontent', PARAM_RAW);

        $this->add_action_buttons();
        // $this->set_data($instance);
    }


    // Custom validation should be added here.
    function validation($data, $files)
    {
        global $CFG, $DB, $USER;

        $validated = array();
        $data = (object)$data;
        // if (empty($data->content)) {
        //     $validated['content'] = '- ' . get_string('err_required', 'form');
        // }
        return $validated;
    }
}
