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

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class courseform extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore! 
        $instance = $this->_customdata['instance'];
        $id = $this->_customdata['id'];
        $options = ['' => 'Select Company'];
        if ($instance) {
            if ($instance->companyid) {
                $companyies = $DB->get_records_menu('company', ['suspended' => 0, 'id'  => $instance->companyid], $sort = 'id desc', $fields = '*', $limitfrom = 0, $limitnum = 0);
            } else {
                $companyies = $DB->get_records_menu('company', ['suspended' => 0], $sort = 'id desc', $fields = '*', $limitfrom = 0, $limitnum = 0);
            }
        }
        $options = $options + $companyies;
        $mform->addElement('hidden', 'domainid', $id);
        $mform->setType('domainid', PARAM_INT);
        $mform->addElement('hidden', 'company_id', $instance->companyid);
        $mform->setType('company_id', PARAM_INT);

        $mform->addElement('select', 'companyid', get_string('selectcompany', 'local_recommendation'), $options);
        $mform->addRule('companyid', get_string('required'), 'required', 'extraruledata', 'server', false, false);

        $mform->addElement('text', 'domain', get_string('domainname', 'local_recommendation'));
        $mform->setType('domain', PARAM_TEXT);
        $mform->addRule('domain', get_string('required'), 'required', 'extraruledata', 'server', false, false);


        $this->add_action_buttons();
        $this->set_data($instance);
    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        global $CFG, $DB, $USER;

        $validated = array();
        $data = (object)$data;
        $domain = $data->domain;
        if ($data->company_id != $data->companyid) {
            if ($DB->record_exists_sql('SELECT * FROM {company_course_domain} WHERE ' . $DB->sql_compare_text('domain') . ' = ' .$DB->sql_compare_text(':domain').'', ['domain' => "$domain"])) {
                $validated['domain'] = get_string('domainexist', 'local_recommendation');
            }
        }
        else {
            if (!$DB->record_exists_sql('SELECT * FROM {company_course_domain} WHERE ' . $DB->sql_compare_text('domain') . ' = ' .$DB->sql_compare_text(':domain').' AND id = '.$data->company_id.'', ['domain' => "$domain"])) {
                if ($DB->record_exists_sql('SELECT * FROM {company_course_domain} WHERE ' . $DB->sql_compare_text('domain') . ' = ' .$DB->sql_compare_text(':domain').'', ['domain' => "$domain"])) {
                $validated['domain'] = get_string('domainexist', 'local_recommendation');
                }
            }
        }
        return $validated;
    }
}
