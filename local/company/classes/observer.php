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
 * @package   local_company
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_company;

class observer
{

    /**
     * Consume user_loggedin event
     * @param object $event the event object
     */
    public static function create_directory($event)
    {
        global $DB, $CFG, $USER;

        // Get the relevant event date (course_completed event).
        $data = $event->get_data();
        $companyid = $data['other']['companyid'];
        $companyrecord = $DB->get_record('company', ['id' => $companyid]);
        // Create a new moodle data folder.
        $folderName = 'moodledata_' . $companyrecord->shortname;
        $path = $CFG->dataroot;

        $folderPath = dirname($path) . "/" . $folderName;

        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        } else {
            $folderPath = dirname($path) . "/" . $folderName . "_" . time();
            mkdir($folderPath, 0777, true);
        }
        $newfoldername = basename($folderPath);

        $insertrecord = new \stdClass();
        $insertrecord->userid = $USER->id;
        $insertrecord->companyid = $companyid;
        $insertrecord->directory_name = $newfoldername;
        $insertrecord->timecreated = time();
        $DB->insert_record('company_directory', $insertrecord);
        return true;
    }
}
