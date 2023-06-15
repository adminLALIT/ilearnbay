<?php
// This file is part of Moodle - https://moodle.org/
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
 * The interface library between the core and the subsystem.
 *
 * @package     block_dashboard
 * @copyright   2019 Peter Dias <peter@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


function block_dashboard_ram_used()
{
    global $DB;
    // Execute the du command to get the ram usage.
    $outputram = shell_exec('free -mh');
    $outputram = explode(":", $outputram);
    $outputramkey = explode(" ", $outputram[0]);
    $outputramvalue = explode(" ", $outputram[1]);
    $keys = array_values(array_filter($outputramkey));
    $values = array_values(array_filter($outputramvalue));
    $ramusage = array_combine($keys, $values);
    $ramused = $ramusage['used'];
    return $ramused;
}

function block_dashboard_diskused_byadmin()
{
    global $DB, $CFG;
    // Execute the du command to get the ram usage.
    $size = 0;
    $alldirectories = $DB->get_records('company_directory', []);
    $path = dirname($CFG->dataroot);
    foreach ($alldirectories as $directory) {
        $folderpath = $path . "/" . $directory->directory_name;
        // Execute the du command to get the folder size.
        $command = "du -s $folderpath";
        $output = shell_exec($command);
        // Extract the folder size from the command output.
        $size = trim(explode("\t", $output)[0]) + $size;
    }
    $folderpath = $CFG->dataroot;
    // Execute the du command to get the folder size.
    $command = "du -s $folderpath";
    $output = shell_exec($command);
    // Extract the folder size from the command output.
    $size = trim(explode("\t", $output)[0]) + $size;
    $usage = round($size / 1024, 2) . "M";
    return $usage;
}

function diskusage_by_current_directory()
{
    global $DB, $CFG;

    $folderPath = $CFG->dataroot;
    // Execute the du command to get the folder size.
    $command = "du -sh $folderPath";
    $output = shell_exec($command);
    // Extract the folder size from the command output.
    $usage = trim(explode("\t", $output)[0]);
    return $usage;
}

function companyid_by_userid($id)
{
    global $DB, $CFG;
    $companyid =  $DB->get_field('company_users', 'companyid', ['userid' => $id]);
    return $companyid;
}

function block_dashboard_get_online_user()
{
    global $DB, $CFG, $USER;
    $companyid = companyid_by_userid($USER->id);

    $uservisibilityselect = "";
    if ($CFG->block_online_users_onlinestatushiding) {
        $uservisibility = ", up.value AS uservisibility";
        $uservisibilityselect = "AND (" . $DB->sql_cast_char2int('up.value') . " = 1
                                OR up.value IS NULL
                                OR u.id = :userid)";
    }

    $timetoshowusers = 300; //Seconds default
    if (isset($CFG->block_online_users_timetosee)) {
        $timetoshowusers = $CFG->block_online_users_timetosee * 60;
    }
    $now = time();
    $timefrom = 100 * floor(($now - $timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache.

    $params['now'] = $now;
    $params['timefrom'] = $timefrom;
    $params['userid'] = $USER->id;
    $params['name'] = 'block_online_users_uservisibility';
    $params['companyid'] = $companyid;
    //Calculate minutes
    $minutes  = floor($timetoshowusers / 60);
    $periodminutes = get_string('periodnminutes', 'block_online_users', $minutes);

    $csql = "SELECT COUNT(u.id)
FROM {user} u 
LEFT JOIN {user_preferences} up ON up.userid = u.id
     AND up.name = :name
WHERE u.lastaccess > :timefrom
     AND u.lastaccess <= :now
     AND u.deleted = 0 AND u.id IN (SELECT userid FROM {company_users} WHERE companyid = :companyid)
     $uservisibilityselect
  ";

    $user =  $DB->count_records_sql($csql, $params);
    return $user . " (" . $periodminutes . ")";
}
