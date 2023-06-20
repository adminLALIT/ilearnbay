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

declare(strict_types=1);

namespace local_course_completion\reportbuilder\datasource;

use lang_string;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use local_course_completion\reportbuilder\local\entities\company;

/**
 * Users datasource
 *
 * @package   local_course_completion
 * @copyright 2021 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class companyuser extends datasource
{

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string
    {
        return get_string('companyuserreport', 'local_course_completion');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void
    {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/local/course_completion/lib.php');
        // Join user entity.
        $userentity = new user();
        $user = $userentity->get_table_alias('user');
        // $userentity->add_joins($enrolmententity->get_joins());
        // $userentity->add_join("JOIN {company_users} cu ON cu.userid = {$user}.id AND {$user}.deleted = 0 AND cu.suspended = 0");

        $this->add_entity($userentity);

        $this->set_main_table('user', $user);

        // Join the company entity.
        $companyentity = new company();
        $usercompany = $companyentity->get_table_alias('company');
       
        $enroljoin = "JOIN {company_users} cu ON cu.userid = {$user}.id";
        if (get_companyid_by_userid($USER->id)) {
            $companyid = get_companyid_by_userid($USER->id);
            $and = "AND {$usercompany}.id = $companyid";
        }
        else {
            $and = '';
        }

        $usercompanyjoin = "JOIN {company} {$usercompany} ON {$usercompany}.id = cu.companyid $and";
        $companyentity->add_joins([$enroljoin, $usercompanyjoin]);
       
        $this->add_entity($companyentity);

        // Add report elements from each of the entities we added to the report.
        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array
    {
        return [
            'user:fullnamewithlink',
            'company:shortname',
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array
    {
        return [
            'company:shortname'
        ];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array
    {
        return [];
    }

    /**
     * Return the conditions values that will be added to the report once is created
     *
     * @return array
     */
    public function get_default_condition_values(): array
    {
        return [];
    }

    /**
     * Return the default sorting that will be added to the report once it is created
     *
     * @return array|int[]
     */
    public function get_default_column_sorting(): array
    {
        return [];
    }
}
