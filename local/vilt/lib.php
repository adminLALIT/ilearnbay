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
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/enrollib.php');


/**
 * get meeting type
 * @return void
 */
function getmeetingtype()
{
    $meetingtypes  = [
        'onlyinvited' => 'Only Invited Users',
        'openuser' => 'Open to registered User',
        'all' => 'All registered User',
        'public' => 'Public'
    ];

    return $meetingtypes;
}


/**
 * is_companymanager
 * @param  int $userid
 * @return void
 */
function is_companymanager($id)
{
    global $DB, $USER;
    $companymanagerrole = $DB->get_field('role', 'id', ['shortname' => 'companymanager']);
    if (user_has_role_assignment($id, $companymanagerrole)) {
        $companyid = $DB->get_record('company_users', ['userid' => $USER->id]);
        return $companyid->companyid;
    } else {
        return false;
    }
}


/**
 * Add webex member
 * @param  int $courseid
 * @param  int $userid
 * @return void
 */
function lp_add_member($courseid, $userid)
{
    global $DB, $USER, $PAGE;
    if ($DB->record_exists('webexmeeting_members', array('courseid' => $courseid, 'userid' => $userid))) {
        // No duplicates!
        return;
    }
    $viltrecord = $DB->get_record('viltrecord', ['courseid' => $courseid], '*', MUST_EXIST);
    $record = new stdClass();
    $record->courseid  = $courseid;
    $record->webexid    = $viltrecord->webexid;
    $record->type    = $viltrecord->meetingtype;
    $record->userid    = $userid;
    $record->assignorid    = $USER->id;
    $record->timecreated = time();
    $DB->insert_record('webexmeeting_members', $record);
    $user = $DB->get_record('user', ['id' => $userid]);
    $course = $DB->get_record('course', ['id' => $courseid]);
    $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
    $a = new stdClass();
    $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
    $eventdatareceipt = new \core\message\message();
    $eventdatareceipt->courseid          = $courseid;
    $eventdatareceipt->modulename        = 'moodle';
    $eventdatareceipt->component         = 'local_vilt';
    $eventdatareceipt->name              = 'meeting_enrolment';
    $eventdatareceipt->userfrom          = core_user::get_noreply_user();
    $eventdatareceipt->userto            = $user;
    $eventdatareceipt->subject           = get_string("newenrolment", 'local_vilt');
    $eventdatareceipt->fullmessage       = get_string('welcometocoursetext', 'local_vilt', $a);
    $eventdatareceipt->fullmessageformat = FORMAT_PLAIN;
    $eventdatareceipt->fullmessagehtml = '';
    $eventdatareceipt->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
    $eventdatareceipt->smallmessage      = '';
      // User image.
      $admin = get_admin();
      $userpicture = new user_picture($USER);
      $userpicture->size = 1; // Use f1 size.
      $userpicture->includetoken = $admin->id; // Generate an out-of-session token for the user receiving the message.
    $eventdatareceipt->customdata = [
        'notificationiconurl' => $userpicture->get_url($PAGE)->out(false),
        'actionbuttons' => [
            'send' => get_string_manager()->get_string('send', 'message', null, $eventdatareceipt->userto->lang),
        ],
        'placeholders' => [
            'send' => get_string_manager()->get_string('writeamessage', 'message', null, $eventdatareceipt->userto->lang),
        ],
    ];
    message_send($eventdatareceipt);
    
    $instances = $DB->get_records_sql("SELECT * FROM {enrol} WHERE courseid = " . $courseid . " AND status = 0 AND enrol = 'manual'");
    foreach ($instances as $instance) {
        $enrolmethod = 'manual';
        $roleid = 5;
        $plugin = enrol_get_plugin($instance->enrol);
        $now = time();
        $timestart = intval(substr($now, 0, 8) . '00') - 1;
        $timeend = 0;
        $result = $plugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
        
    }

}

/**
 * Remove webex member
 * @param  int $courseid
 * @param  int $userid
 * @return void
 */
function lp_remove_member($courseid, $userid)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/lib/enrollib.php');
    $sql = "SELECT e.*
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
                 WHERE ue.userid = :userid";
    $params = array('userid' => $userid);

    $rs = $DB->get_records_sql($sql, $params);
    //print_r($rs);
    foreach ($rs as $instance) {
        $cplugin = enrol_get_plugin($instance->enrol);
        $cplugin->unenrol_user($instance, $userid);
    }
    //remove enroll courses
    $DB->delete_records('webexmeeting_members', array('courseid' => $courseid, 'userid' => $userid));
}

/**
 * Produces a part of SQL query to filter cohorts by the search string
 *
 * Called from {@link cohort_get_cohorts()}, {@link cohort_get_all_cohorts()} and {@link cohort_get_available_cohorts()}
 *
 * @access private
 *
 * @param string $search search string
 * @param string $tablealias alias of cohort table in the SQL query (highly recommended if other tables are used in query)
 * @return array of two elements - SQL condition and array of named parameters
 */
function lp_get_search_query($search, $tablealias = '')
{
    global $DB;
    $params = array();
    if (empty($search)) {
        // This function should not be called if there is no search string, just in case return dummy query.
        return array('1=1', $params);
    }
    if ($tablealias && substr($tablealias, -1) !== '.') {
        $tablealias .= '.';
    }
    $searchparam = '%' . $DB->sql_like_escape($search) . '%';
    $conditions = array();
    $fields = array('name', 'idnumber', 'description');
    $cnt = 0;
    foreach ($fields as $field) {
        $conditions[] = $DB->sql_like($tablealias . $field, ':csearch' . $cnt, false);
        $params['csearch' . $cnt] = $searchparam;
        $cnt++;
    }
    $sql = '(' . implode(' OR ', $conditions) . ')';
    return array($sql, $params);
}




function attrsyntax_toarray($attrsyntax)
{ // TODO : protected
    global $DB;

    $attrsyntax_object = json_decode($attrsyntax);
    $rules = $attrsyntax_object->rules;

    $customuserfields = array();
    foreach ($DB->get_records('user_info_field') as $customfieldrecord) {
        $customuserfields[$customfieldrecord->id] = $customfieldrecord->shortname;
    }
    return array(
        'customuserfields' => $customuserfields,
        'rules'            => $rules
    );
}
function arraysyntax_tosql($arraysyntax, &$join_id = 0)
{
    global $DB;
    $select = '';
    $where = '1=1';
    $params = array();
    $customuserfields = $arraysyntax['customuserfields'];

    foreach ($arraysyntax['rules'] as $rule) {
        if (isset($rule->cond_op)) {
            $where .= ' ' . strtoupper($rule->cond_op) . ' ';
        } else {
            $where .= ' AND ';
        }
        // first just check if we have a value 'ANY' to enroll all people :
        if (isset($rule->value) && $rule->value == 'ANY') {
            $where .= '1=1';
            continue;
        }
        if (isset($rule->rules)) {
            $sub_arraysyntax = array(
                'customuserfields' => $customuserfields,
                'rules'            => $rule->rules
            );
            $sub_sql = arraysyntax_tosql($sub_arraysyntax, $join_id);
            $select .= ' ' . $sub_sql['select'] . ' ';
            $where .= ' ( ' . $sub_sql['where'] . ' ) ';
            $params = array_merge($params, $sub_sql['params']);
        } else {
            if ($customkey = array_search($rule->param, $customuserfields, true)) {
                // custom user field actually exists
                $join_id++;
                $data = 'd' . $join_id . '.data';
                $select .= ' RIGHT JOIN {user_info_data} d' . $join_id . ' ON d' . $join_id . '.userid = u.id AND d' . $join_id . '.fieldid = ' . $customkey;
                $where .= ' (' . $DB->sql_compare_text($data) . ' = ' . $DB->sql_compare_text('?') . ' OR ' . $DB->sql_like(
                    $DB->sql_compare_text($data),
                    '?'
                ) . ' OR ' . $DB->sql_like(
                    $DB->sql_compare_text($data),
                    '?'
                ) . ' OR ' . $DB->sql_like($DB->sql_compare_text($data), '?') . ')';
                array_push(
                    $params,
                    $rule->value,
                    '%;' . $rule->value,
                    $rule->value . ';%',
                    '%;' . $rule->value . ';%'
                );
            }
        }
    }

    $where = preg_replace('/^1=1 AND/', '', $where);
    $where = preg_replace('/^1=1 OR/', '', $where);
    $where = preg_replace('/^1=1/', '', $where);

    return array(
        'select' => $select,
        'where'  => $where,
        'params' => $params
    );
}
