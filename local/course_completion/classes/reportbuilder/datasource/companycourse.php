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
use core_reportbuilder\local\helpers\database;
use core_course\reportbuilder\local\entities\course_category;
use core_reportbuilder\local\entities\course;
use local_course_completion\reportbuilder\local\entities\company;

/**
 * Users datasource
 *
 * @package   local_course_completion
 * @copyright 2021 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class companycourse extends datasource
{

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string
    {
        return get_string('companycoursereport', 'local_course_completion');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void
    {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/local/course_completion/lib.php');
        $courseentity = new course();
        $coursetablealias = $courseentity->get_table_alias('course');

        // Exclude site course.
        $paramsiteid = database::generate_param_name();

        $this->set_main_table('course', $coursetablealias);
        $this->add_base_condition_sql("{$coursetablealias}.id != :{$paramsiteid}", [$paramsiteid => SITEID]);

        $this->add_entity($courseentity);

        // Join the course category entity.
        $coursecatentity = new course_category();
        $coursecattablealias = $coursecatentity->get_table_alias('course_categories');
        $this->add_entity($coursecatentity
            ->add_join("JOIN {course_categories} {$coursecattablealias}
                ON {$coursecattablealias}.id = {$coursetablealias}.category"));

        // Join the company entity.
        $companyentity = new company();
        $companycourse = $companyentity->get_table_alias('company');   

        if (get_companyid_by_userid($USER->id)) {
            $companyid = get_companyid_by_userid($USER->id);
            $and = "AND {$companycourse}.id = $companyid";
        }
        else {
            $and = '';
        }  
        $enroljoin = "JOIN {company_course} cc ON cc.courseid = {$coursetablealias}.id";
        $companycoursejoin = "JOIN {company} {$companycourse} ON {$companycourse}.id = cc.companyid $and";
        $companyentity->add_joins([$enroljoin, $companycoursejoin]);
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
            'course:shortname',
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
            'course:shortname'
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
