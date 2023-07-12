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

 function is_curator(){
    global $DB, $USER;

    if ($check = $DB->record_exists('role', ['shortname' => 'curator'])) {
        $curatorrole = $DB->get_field('role', 'id', ['shortname' => 'curator']);
        if (user_has_role_assignment($USER->id, $curatorrole)) {
           return true;
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }
 }

 function is_student(){
    global $DB, $USER;

    if ($check = $DB->record_exists('role', ['shortname' => 'student'])) {
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        if (user_has_role_assignment($USER->id, $studentrole)) {
           return true;
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }
 }

