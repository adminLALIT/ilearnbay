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
 * Cohort UI related functions and classes.
 *
 * @package    core_cohort
 * @copyright  2012 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/vilt/lib.php');
require_once($CFG->dirroot . '/local/vilt/selector/lib.php');

/**
 * Cohort assignment candidates
 */
class lp_candidate_selector extends user1_selector_base
{
    protected $lpid, $companyid;

    public function __construct($name, $options)
    {
        $this->lpid = $options['lpid'];
        $this->companyid = $options['companyid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search)
    {

        global $DB;

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $params['lpid'] = $this->lpid;
        $params['companyid'] = $this->companyid;

        $sql = "select u.* FROM {user} u JOIN {company_users} cu ON cu.userid = u.id WHERE cu.companyid = :companyid";

        $select = 'Select u.* FROM {user} u JOIN {company_users} cu ON cu.userid = u.id';
        $where = 'WHERE cu.companyid = :companyid';
        $where .= ' AND u.deleted=0';

        if ($search) {

            $users = $DB->get_records_sql($select . " WHERE" . $wherecondition, $params);
        } else {
            $users = $DB->get_records_sql($select . " " . $where, $params);
        }

        $users = array_keys($users);
        if (!empty($users)) {
            list($insql, $paramins) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'u', true);
            $inchksql = "AND u.id $insql";
        } else {
            $paramins = array();
            $inchksql = '';
        }
        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u WHERE $wherecondition $inchksql";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
       
        $order = ' ORDER BY ' . $sort;
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, array_merge($params, $paramins));
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $paramins, $sortparams));


        if (empty($availableusers) || empty($users)) {
            return array();
        }
        if ($search) {
            $groupname = get_string('potusersmatching', 'cohort', $search);
        } else {
            $groupname = get_string('potusers', 'cohort');
        }
        return array($groupname => $availableusers);
    }


    protected function get_options()
    {
        $options = parent::get_options();
        $options['lpid'] = $this->lpid;
        $options['file'] = 'local/vilt/locallib.php';
        return $options;
    }
}


/**
 * Cohort assignment candidates
 */
class lp_existing_selector extends user1_selector_base
{
    protected $lpid;

    public function __construct($name, $options)
    {
        $this->lpid = $options['lpid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search)
    {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['courseid'] = $this->lpid;

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u JOIN {user_enrolments} ue ON ue.userid = u.id JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid= :courseid AND $wherecondition";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }


        if ($search) {
            $groupname = get_string('currentusersmatching', 'cohort', $search);
        } else {
            $groupname = get_string('currentusers', 'cohort');
        }

        return array($groupname => $availableusers);
    }

    protected function get_options()
    {
        $options = parent::get_options();
        $options['lpid'] = $this->lpid;
        $options['file'] = 'local/vilt/locallib.php';
        return $options;
    }
}
