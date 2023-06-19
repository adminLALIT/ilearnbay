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

namespace local_course_completion\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use stdClass;

/**
 * company entity implementation
 *
 * @package     core_course
 * @copyright   2022 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'company' => 'comp'
        ];
    }

    /**
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('companydetail', 'local_course_completion');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        foreach ($this->get_all_filters() as $filter) {
            
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('company');

        // shortname column.
        $columns[] = (new column(
            'shortname',
            new lang_string('companyshortname', 'local_course_completion'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.shortname");

             // name column.
        $columns[] = (new column(
            'name',
            new lang_string('companyname', 'local_course_completion'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.name");
       
        return $columns;
    }

        /**
     * company fields
     *
     * @return lang_string[]
     */
    protected function get_company_fields(): array {
        return [
            'shortname' => new lang_string('companyshortname', 'local_course_completion'), 
            'name' => new lang_string('companyname', 'local_course_completion'), 
        ];
    }


       /**
     * Return appropriate column type for given company field
     *
     * @param string $companyfield
     * @return int
     */
    protected function get_company_field_type(string $companyfield): int {
        switch ($companyfield) {
            case 'shortname':
                $fieldtype = column::TYPE_TEXT;
                break;
            default:
                $fieldtype = column::TYPE_TEXT;
                break;
        }

        return $fieldtype;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;

        $filters = [];
        $tablealias = $this->get_table_alias('company');

        // company fields filters.
        $fields = $this->get_company_fields();
        
        foreach ($fields as $field => $name) {
            $filterfieldsql = "{$tablealias}.{$field}";
                $classname = text::class;

            $filter = (new filter(
                $classname,
                $field,
                $name,
                $this->get_entity_name(),
                $filterfieldsql
            ))
                ->add_joins($this->get_joins());

            $filters[] = $filter;
        }
  
        return $filters;
    }
}
