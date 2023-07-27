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

class domain_assign extends moodleform
{
    // Add elements to form
    public function definition()
    {
        global $CFG, $USER, $DB;

        $mform = $this->_form; // Don't forget the underscore! 
        $domaininital = ['' => 'Select']; 
        $studentdomain = $DB->get_records_sql("SELECT * FROM {domain_mapping} WHERE profilefield IN (SELECT fieldid FROM {user_info_data} WHERE userid = $USER->id)");
        $domainid = [];
        foreach($studentdomain as $domainvalue){
            $domainid[] = $domainvalue->domainid;
        }
        
        if (count($domainid) > 0) {
            $domainids = implode(",", $domainid);
            $companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id]);
            $domain = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE companyid = (SELECT companyid FROM {company_users} WHERE userid = $USER->id) AND id  NOT IN ($domainids) AND id NOT IN (SELECT domainid FROM {additional_domains} WHERE studentuserid = $USER->id)");
            $domaininital = $domaininital + $domain;
        }
       
        $mform->addElement('hidden', 'companyid', $companyid);
        $mform->setType('companyid', PARAM_RAW);

        $mform->addElement('select', 'domainid', get_string('selectdomain', 'local_recommendation'), $domaininital);

        $this->add_action_buttons();
     
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
