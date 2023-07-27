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
require_once("$CFG->dirroot/local/course_completion/lib.php");

class assign_course_form extends moodleform
{
    // Add elements to form
    public function definition()
    {
        global $CFG, $USER, $DB;

        $mform = $this->_form; // Don't forget the underscore! 
        $id = $this->_customdata['id'];
        $instance = $this->_customdata['instance'];

        $curatorrole = $DB->get_field('role', 'id', ['shortname' => 'curator']);
        $context = context_system::instance();
        $curatoruser = get_role_users($curatorrole, $context);

        foreach($curatoruser as $userid => $name){
            $curators[$userid] = fullname($name);
        }
       
        if ($id) {
            $companyies = $DB->get_records_menu('company', ['suspended' => 0, 'id' => $instance->companyid], $sort = 'id desc', $fields = '*', $limitfrom = 0, $limitnum = 0);        // Add the new key-value pair at the beginning of the array
            $domaininitial = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id = $instance->domain ORDER BY id desc");
            $courseinitial = $DB->get_records_sql_menu("SELECT id, fullname FROM {course} WHERE id = $instance->course");;
        } else {
            $companyies = $DB->get_records_menu('company', ['suspended' => 0], $sort = 'id desc', $fields = '*', $limitfrom = 0, $limitnum = 0);        // Add the new key-value pair at the beginning of the array
            $domaininitial = ['' => 'Select Domain'];
            $courseinitial = ['' => 'Select Course'];
        }

        $contentinitial = ['' => 'Select Content', 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'wikipedia' => 'Wikipedia'];
        $curatorinitial = ['' => 'Select Curator'];
        $curatorinitial = $curatorinitial + $curators;
        $selectcompany = array('' => 'Select Company') + $companyies;
        if (get_companyid_by_userid($USER->id)) {
            $companyid =  get_companyid_by_userid($USER->id);
            $domains = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id = $companyid ORDER BY id desc");
        }
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        if (is_siteadmin()) {
            $mform->addElement('select', 'companyid', get_string('selectcompany', 'local_recommendation'), $selectcompany, array('onchange' => 'javascript:getdomain();'));
            $mform->addRule('companyid', get_string('required'), 'required', 'extraruledata', 'server', false, false);

            $mform->addElement('select', 'course', get_string('selectcourse', 'local_recommendation'), $courseinitial);
            $mform->addElement('select', 'domain', get_string('selectdomain', 'local_recommendation'), $domaininitial);
        } else {
            $selectcourse =   $mform->addElement('select', 'course', get_string('selectcourse', 'local_recommendation'), $courseinitial);
            $selectdomain =   $mform->addElement('select', 'domain', get_string('selectdomain', 'local_recommendation'), $domains);
        }
        $mform->addRule('domain', get_string('required'), 'required', 'extraruledata', 'server', false, false);
        $mform->addRule('course', get_string('required'), 'required', 'extraruledata', 'server', false, false);
        $options = array(                                                                                                           
            'multiple' => true,                                                  
            'noselectionstring' => get_string('selectcontent', 'local_recommendation'),                                                                
        );         
        $mform->addElement('autocomplete', 'content', get_string('selectcontent', 'local_recommendation'), $contentinitial, $options);
        $mform->addRule('content', get_string('required'), 'required', 'extraruledata', 'server', false, false);

        $mform->addElement('select', 'curatoruserid', get_string('selectcurator', 'local_recommendation'), $curatorinitial);
        $mform->addRule('curatoruserid', get_string('required'), 'required', 'extraruledata', 'server', false, false);

        $this->add_action_buttons();
        $this->set_data($instance);
    }


    // Custom validation should be added here.
    function validation($data, $files)
    {
        global $CFG, $DB, $USER;

        $validated = array();
        $data = (object)$data;
        if (empty($data->content)) {
            $validated['content'] = '- ' . get_string('err_required', 'form');
        }
        return $validated;
    }
}
