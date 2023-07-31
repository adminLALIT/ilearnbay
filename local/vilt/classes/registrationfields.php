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
 * File containing the step 1 of the upload form.
 *
 * @package    local_vilt
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vilt;

defined('MOODLE_INTERNAL') || die();
//moodleform is defined in formslib.php

require_once("$CFG->libdir/formslib.php");

// namespace local_vilt;
class registrationfields extends \moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB, $USER;

        $mform = $this->_form;
        $instance = $this->_customdata['instance'];
        $id = $this->_customdata['id'];

        $mform->addElement('hidden', 'meetingid', $id);
        $mform->settype('meetingid', PARAM_INT);
        $mform->addElement('hidden', 'course', $instance->course);
        $mform->settype('course', PARAM_INT);
        $mform->addElement('hidden', 'formid', $instance->id);
        $mform->settype('formid', PARAM_INT);
      
        $options = array(
            'multiple' => true,
            'noselectionstring' => get_string('selectfield', 'local_vilt'),
        );
     
        $commonfields = $DB->get_records_sql_menu("SELECT uif.id, uif.name FROM {user_info_field} uif WHERE uif.categoryid NOT IN (SELECT id FROM {company} WHERE suspended = 0)");
        $mform->addElement('autocomplete', 'profilefields', get_string('formsetup', 'local_vilt'), $commonfields, $options);


        $this->add_action_buttons();
        $this->set_data($instance);
    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        global $DB, $id;
        $validated = array();
        $data = (object)$data;
        return $validated;
    }
}
