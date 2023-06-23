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

class courseform extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG, $DB;
       
        $mform = $this->_form; // Don't forget the underscore! 
        $options = [''=> 'Select Company'];
        $companyies = $DB->get_records_menu('company', ['suspended' => 0], $sort='id desc', $fields='*', $limitfrom=0, $limitnum=0);
        $options = $options + $companyies;
        $mform->addElement('select', 'company', get_string('selectcompany', 'local_recommendation'), $options,);

        $mform->addElement('text', 'domain', get_string('domainname', 'local_recommendation'));
        $mform->setType('domain', PARAM_TEXT);

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
?>