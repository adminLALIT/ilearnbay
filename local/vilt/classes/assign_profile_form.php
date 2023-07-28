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
class assign_profile_form extends \moodleform
{
    public function definition()
    {
        global $CFG, $DB, $USER;

        $mform = $this->_form;
        $companyid = $this->_customdata['companyid'];
        $courseid = $this->_customdata['courseid'];
        $instance = $this->_customdata['instance'];

        $mform->addElement('hidden', 'companyid', $companyid);
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $options = ['' => 'Choose a profile'];
        // Get fields and store them indexed by shortname.
        $commonfields = $DB->get_records_sql_menu("SELECT uif.id, uif.name FROM {user_info_field} uif WHERE uif.categoryid NOT IN (SELECT id FROM {company} WHERE suspended = 0)");
        $options = $options + $commonfields;
        $repeatarray = array();
        $repeatarray[] = $mform->createElement('html', '<div class="separate">');
        $repeatarray[] = $mform->createElement('select', 'profile', get_string('chooseprofile', 'local_vilt'), $options);
        $repeatarray[] = $mform->createElement('html', '<div class="qheader" style="display:flex; padding-left: 21%; gap: 91px;">');
        $repeatarray[] = $mform->createElement('text', 'profilevalue');
        $repeatarray[] = $mform->createElement('html', '<a href="#" class="mt-2 text-dark"><i class="fa fa-trash delete"></i></a>');
        $repeatarray[] = $mform->createElement('html', '</div>');
        $repeatarray[] = $mform->createElement('html', '</div>');

        $repeatno = $instance->repeatelement;

        $repeateloptions = array();

        $mform->setType('profilevalue', PARAM_TEXT);

        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeateloptions,
            'profile_repeats',
            'profile_add_fields',
            1,
            null,
            true
        );

        $this->add_action_buttons();
        $this->set_data($instance);
    }

    //Custom validation should be added here
    function validation($data, $files)
    {
        global $DB, $id;
        $validated = array();
        $data = (object)$data;
        if(count($data->profile) != count(array_unique($data->profile))){
            redirect('assignprofile.php?id='.$data->courseid.'&company='.$data->companyid.'', 'Duplicate fields not allowed.', null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}
