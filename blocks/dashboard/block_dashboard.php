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
 * dashboard block.
 *
 * @package    block_dashboard
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_dashboard extends block_base
{
    function init()
    {
        $this->title = get_string('pluginname', 'block_dashboard');
    }

    function has_config()
    {
        return false;
    }

    function get_content()
    {
        global $USER, $CFG, $DB, $OUTPUT;
        require_once($CFG->dirroot.'/blocks/dashboard/lib.php');
      
        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = 'Overall Performance';

        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        if (is_siteadmin()) {
            $companycount = $DB->count_records('company', ['suspended' => 0]);
            $usercount = $DB->count_records('user', ['suspended' => 0, 'deleted' => 0]);
            $usage = block_dashboard_diskused_byadmin();
            $ramused = block_dashboard_ram_used();
            $this->content->text .= "<div>Total Company: $companycount Total Users: $usercount Disk Usage: $usage RAM Usage: $ramused </div>";

        } else {
            $usercount = $DB->count_records_sql("SELECT count(id) FROM {company_users} WHERE companyid = (SELECT companyid FROM {company_users} WHERE userid = $USER->id)");
            $coursecount = $DB->count_records_sql("SELECT count(id) FROM {company_course} WHERE companyid = (SELECT companyid FROM {company_users} WHERE userid = $USER->id)");
            $usage = diskusage_by_current_directory();
            $onlineuser = block_dashboard_get_online_user();
            $this->content->text .= "<div>Total Users: $usercount Total Courses: $coursecount Online Users: $onlineuser Disk Usage: $usage </div>";

        }

        return $this->content;
    }
}
