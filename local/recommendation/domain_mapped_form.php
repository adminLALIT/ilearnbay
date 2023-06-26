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
class domain_mapped_form extends moodleform {
    // Add elements to form
    public function definition() {
        global $CFG, $USER, $DB;
        $id = optional_param('id', 0, PARAM_INT);
     
        $mform = $this->_form; // Don't forget the underscore! 

        $companyies = $DB->get_records_menu('company', ['suspended' => 0], $sort='id desc', $fields='*', $limitfrom=0, $limitnum=0);        // Add the new key-value pair at the beginning of the array
        $selectcompany = array('' => 'Select') + $companyies;
        if (get_companyid_by_userid($USER->id)) {
           $companyid =  get_companyid_by_userid($USER->id);
           $domains = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE companyid = $companyid ORDER BY id desc");
        }
            $profield = $DB->get_records_sql_menu("SELECT id, name FROM {user_info_field} ORDER BY id desc");
        $mform->addElement('hidden', 'domainid', $id);
        $mform->setType('domainid', PARAM_INT);
        $domaininitial = ['' => 'Select Domain'];
        if (is_siteadmin()) {
            $mform->addElement('select', 'companyid', get_string('selectcompany', 'local_recommendation'), $selectcompany, array('onchange' => 'javascript:getdomain();'));
            $mform->addRule('companyid', get_string('required'), 'required', 'extraruledata', 'server', false, false);
            $mform->addElement('select', 'domain', get_string('selectdomain', 'local_recommendation'), $domaininitial);
        }
        else {
            $mform->addElement('select', 'domain', get_string('selectdomain', 'local_recommendation'), $domains);
        }
        $mform->addRule('domain', get_string('required'), 'required', 'extraruledata', 'server', false, false);
        
        $mform->addElement('header', 'profilerule', get_string('profilerule', 'local_recommendation'));
        $mform->setExpanded('profilerule', false);

        $options = array(                                                                                                           
            'multiple' => true,                                                  
            'noselectionstring' => 'Please Select',                                                                
        ); 
        $mform->addElement('autocomplete', 'profilefield', get_string('profilefield', 'local_recommendation'), $profield, $options);
        $mform->addRule('profilefield', get_string('required'), 'required', null, 'server');
        
        $mform->addElement('text', 'profiletext', null);
        $mform->addRule('profiletext', get_string('required'), 'required', 'extraruledata', 'server', false, false);
        $mform->setType('profiletext', PARAM_TEXT);
        
        $this->add_action_buttons();
         
    }
    // Custom validation should be added here.
    function validation($data, $files) {
        global $CFG, $DB, $USER;

        $validated = array();
        $data = (object)$data;
        if (empty($data->profilefield)) {
            $validated['profilefield'] = '- '.get_string('err_required', 'form');
        }
        return $validated;
    }
}
