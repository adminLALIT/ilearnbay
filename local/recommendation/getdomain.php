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

require_once('../../config.php');
require_once('lib.php');

global $DB;
$domainid = $_POST['domainid'];
$courseoptions = '';

if ($domainid) {
    $courseoptions = '<option value="">Select..</option>';
    if (is_student()) {
        $enrolledcourses =  enrol_get_users_courses($USER->id, $onlyactive = false, $fields = null, $sort = null);
        foreach($enrolledcourses as $courses){
         $courseids[] = $courses->id;
        }
        $courses = implode(",", $courseids);
        $assigncourses = $DB->get_records_sql_menu("SELECT id, fullname FROM {course} WHERE id IN (SELECT course FROM {curator_assign_course} WHERE domain = $domainid) AND id IN ($courses)");
    }
    else {
        $assigncourses = $DB->get_records_sql_menu("SELECT id, fullname FROM {course} WHERE id IN (SELECT course FROM {curator_assign_course} WHERE curatoruserid = $USER->id AND domain = $domainid)");
     }
    if (!empty($assigncourses)) {
        foreach($assigncourses as $key => $coursevalue){
            $courseoptions .= '<option value="'.$key.'">'.$coursevalue.'</option>';      
        }
    }
}

$json = [];
$json['courseoptions'] = $courseoptions;
echo json_encode($json);

?>