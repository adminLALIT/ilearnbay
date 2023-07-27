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

class filterform extends \moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB;

        $mform = $this->_form;
        $options = array('' => 'Select Company');
        $companies = $DB->get_records_menu('company', ['suspended' => 0]);
        $options = $options + $companies;
        $mform->addElement('select', 'companyid', get_string('company', 'local_vilt'), $options);

        $this->add_action_buttons();

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
