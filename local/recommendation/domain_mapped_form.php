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
class domain_mapped_form extends moodleform
{
    // Add elements to form
    public function definition()
    {
        global $CFG, $USER, $DB;

        $mform = $this->_form; // Don't forget the underscore! 
        $id = $this->_customdata['id'];
        $instance = $this->_customdata['instance'];
        if ($id) {
            $companyies = $DB->get_records_menu('company', ['suspended' => 0, 'id' => $instance->companyid], $sort = 'id desc', $fields = '*', $limitfrom = 0, $limitnum = 0);        // Add the new key-value pair at the beginning of the array
            $domaininitial = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id = $instance->domainid ORDER BY id desc");
        } else {
            $companyies = $DB->get_records_menu('company', ['suspended' => 0], $sort = 'id desc', $fields = '*', $limitfrom = 0, $limitnum = 0);        // Add the new key-value pair at the beginning of the array
            $domaininitial = ['' => 'Select Domain'];
        }

        $selectcompany = array('' => 'Select') + $companyies;
        if (get_companyid_by_userid($USER->id)) {
            $companyid =  get_companyid_by_userid($USER->id);
            $domains = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id = $companyid ORDER BY id desc");
        }
        $profield = $DB->get_records_sql_menu("SELECT id, name FROM {user_info_field} ORDER BY id desc");

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        if (is_siteadmin()) {
            $mform->addElement('select', 'companyid', get_string('selectcompany', 'local_recommendation'), $selectcompany, array('onchange' => 'javascript:getdomain();'));
            $mform->addRule('companyid', get_string('required'), 'required', 'extraruledata', 'server', false, false);

            $select =   $mform->addElement('select', 'domain', get_string('selectdomain', 'local_recommendation'), $domaininitial);
        } else {
            $select =   $mform->addElement('select', 'domain', get_string('selectdomain', 'local_recommendation'), $domains);
        }
        if ($id) {
            $select->setSelected($instance->domainid);
        }

        $mform->addRule('domain', get_string('required'), 'required', 'extraruledata', 'server', false, false);

        $mform->addElement('header', 'profilerule', get_string('profilerule', 'local_recommendation'));
        $mform->setExpanded('profilerule', false);

        $options = array(
            'multiple' => true,
            'noselectionstring' => 'Please Select',
            'class' => 'profilefieldclass'
        );

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('autocomplete', 'profilefield', get_string('profilefield', 'local_recommendation'), $profield, $options);
        $repeatarray[] = $mform->createElement('html', '<div class="qheader" style="display:flex; padding-left: 21%; gap: 91px;">');
        $repeatarray[] = $mform->createElement('text', 'profiletext', null, ['style' => 'margin-left: 5px;']);
        $repeatarray[] = $mform->createElement('html', '<a href="#" class="mt-2 text-dark"><i class="fa fa-trash"></i></a>');
        $repeatarray[] = $mform->createElement('html', '</div>');
       
        $mform->setType('profiletext', PARAM_TEXT);
        $repeatno = 1;
        $repeateloptions = array();
        $repeateloptions['limit']['default'] = 0;
        $repeateloptions['limit']['disabledif'] = array('limitanswers', 'eq', 0);
        $repeateloptions['limit']['rule'] = 'numeric';
        $repeateloptions['limit']['type'] = PARAM_INT;
        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeateloptions,
            'field_repeats',
            'field_add_fields',
            1,
            $this->get_more_choices_string(),
            true
        );

        $this->add_action_buttons();
        $this->set_data($instance);
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    function get_more_choices_string()
    {
        return get_string('addmorefield', 'local_recommendation');
    }

    // Custom validation should be added here.
    function validation($data, $files)
    {
        global $CFG, $DB, $USER;

        $validated = array();
        $data = (object)$data;
        // if (empty($data->profilefield)) {
        //     $validated['profilefield'] = '- ' . get_string('err_required', 'form');
        // }
        return $validated;
    }
}
