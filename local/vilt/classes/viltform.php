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
class viltform extends \moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB, $USER;

        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $editoroptions = $this->_customdata['editoroptions'];
        $instance = $this->_customdata['instance'];
        $id = $this->_customdata['id'];
        if ($id) {
            $attributes = 'disabled';
        } else {
            $attributes = '';
        }
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('hidden', 'meetingid', $id);
        $mform->settype('meetingid', PARAM_INT);
        $mform->addElement('hidden', 'course', $instance->course);
        $mform->settype('course', PARAM_INT);

        if (is_companymanager($USER->id)) {
            $companyid = is_companymanager($USER->id);
            $companies = $DB->get_records_menu('company', ['suspended' => 0, 'id' => $companyid]);
        }
        else {
            $companies = $DB->get_records_menu('company', ['suspended' => 0]);
        }
        $mform->addElement('select', 'companyid', get_string('company', 'local_vilt'), $companies, $attributes);

        $meetingtypes  = getmeetingtype();
        $mform->addElement('select', 'type', get_string('meetingtype', 'webexactivity'), $meetingtypes, $attributes);

        $mform->addElement('text', 'name', get_string('webexactivityname', 'webexactivity'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('editor', 'introeditor', get_string('description', 'local_vilt'), null, $editoroptions);
        $mform->setType('introeditor', PARAM_RAW);

        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'webexactivity'));
        $mform->addRule('starttime', null, 'required', null, 'client');

        $duration = array();
        $duration[] = &$mform->createElement('text', 'duration', '', array('size' => '4'));
        $duration[] = &$mform->createElement('static', 'durationname', '', '(' . get_string('minutes') . ')');
        $mform->addGroup($duration, 'durationgroup', get_string('duration', 'webexactivity'), array(' '), false);
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('durationgroup', null, 'required', null, 'client');
        $mform->setDefault('duration', 20);
        $mform->addHelpButton('durationgroup', 'duration', 'webexactivity');

        $mform->addElement('hidden', 'endtime', time() + (3600 * 24 * 14));
        $mform->setType('endtime', PARAM_INT);

        $this->add_action_buttons();
        $this->set_data($instance);
    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        global $DB, $id;
        $validated = array();
        $data = (object)$data;
        if (empty($data->meetingid)) {
            if ($DB->record_exists('viltrecord', ['meetingtype' => $data->type, 'companyid' => $data->companyid])) {
                $validated['type'] = get_string('alreadyexist', 'local_vilt');
            }
           
            if (!empty($data->name)) {
                if ($DB->record_exists('webexactivity', ['name' => $data->name])) {
                    $validated['name'] = get_string('alreadyexist', 'local_vilt');
                } elseif ($DB->record_exists('course', ['shortname' => $data->name])) {
                    $validated['name'] = get_string('alreadyexist', 'local_vilt');
                }
            }
            if ($DB->record_exists('webexactivity', ['starttime' => $data->starttime])) {
                $validated['starttime'] = get_string('alreadyexist', 'local_vilt');
            }
        } else {
            if (!$DB->record_exists('webexactivity', ['starttime' => $data->starttime, 'id' => $data->meetingid])) {
                if ($DB->record_exists('webexactivity', ['starttime' => $data->starttime])) {
                    $validated['starttime'] = get_string('alreadyexist', 'local_vilt');
                }
            }
            if ($DB->record_exists_sql("SELECT w.* FROM {webexactivity} w WHERE w.name = '$data->name' AND w.id != $data->meetingid")) {
                $validated['name'] = get_string('alreadyexist', 'local_vilt');
            } elseif ($DB->record_exists_sql("SELECT * FROM {course} WHERE shortname = '$data->name' AND id != $data->course")) {
                $validated['name'] = get_string('alreadyexist', 'local_vilt');
            }
        }
        return $validated;
    }
}
