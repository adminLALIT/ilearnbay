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
 * Privacy Subsystem implementation for enrol_phonepe.
 *
 * @package    enrol_phonepe
 * @category   privacy
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_phonepe\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for enrol_phonepe.
 *
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // Transactions store user data.
        \core_privacy\local\metadata\provider,

        // The phonepe enrolment plugin contains user's transactions.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_external_location_link(
            'phonepe.com',
            [
                'os0'        => 'privacy:metadata:enrol_phonepe:phonepe_com:os0',
                'custom'     => 'privacy:metadata:enrol_phonepe:phonepe_com:custom',
                'first_name' => 'privacy:metadata:enrol_phonepe:phonepe_com:first_name',
                'last_name'  => 'privacy:metadata:enrol_phonepe:phonepe_com:last_name',
                'address'    => 'privacy:metadata:enrol_phonepe:phonepe_com:address',
                'city'       => 'privacy:metadata:enrol_phonepe:phonepe_com:city',
                'email'      => 'privacy:metadata:enrol_phonepe:phonepe_com:email',
                'country'    => 'privacy:metadata:enrol_phonepe:phonepe_com:country',
            ],
            'privacy:metadata:enrol_phonepe:phonepe_com'
        );

        // The enrol_phonepe has a DB table that contains user data.
        $collection->add_database_table(
                'enrol_phonepe',
                [
                    'business'            => 'privacy:metadata:enrol_phonepe:enrol_phonepe:business',
                    'receiver_email'      => 'privacy:metadata:enrol_phonepe:enrol_phonepe:receiver_email',
                    'receiver_id'         => 'privacy:metadata:enrol_phonepe:enrol_phonepe:receiver_id',
                    'item_name'           => 'privacy:metadata:enrol_phonepe:enrol_phonepe:item_name',
                    'courseid'            => 'privacy:metadata:enrol_phonepe:enrol_phonepe:courseid',
                    'userid'              => 'privacy:metadata:enrol_phonepe:enrol_phonepe:userid',
                    'instanceid'          => 'privacy:metadata:enrol_phonepe:enrol_phonepe:instanceid',
                    'memo'                => 'privacy:metadata:enrol_phonepe:enrol_phonepe:memo',
                    'tax'                 => 'privacy:metadata:enrol_phonepe:enrol_phonepe:tax',
                    'option_selection1_x' => 'privacy:metadata:enrol_phonepe:enrol_phonepe:option_selection1_x',
                    'payment_status'      => 'privacy:metadata:enrol_phonepe:enrol_phonepe:payment_status',
                    'pending_reason'      => 'privacy:metadata:enrol_phonepe:enrol_phonepe:pending_reason',
                    'reason_code'         => 'privacy:metadata:enrol_phonepe:enrol_phonepe:reason_code',
                    'txn_id'              => 'privacy:metadata:enrol_phonepe:enrol_phonepe:txn_id',
                    'parent_txn_id'       => 'privacy:metadata:enrol_phonepe:enrol_phonepe:parent_txn_id',
                    'payment_type'        => 'privacy:metadata:enrol_phonepe:enrol_phonepe:payment_type',
                    'timeupdated'         => 'privacy:metadata:enrol_phonepe:enrol_phonepe:timeupdated'
                ],
                'privacy:metadata:enrol_phonepe:enrol_phonepe'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Values of ep.receiver_email and ep.business are already normalised to lowercase characters by phonepe,
        // therefore there is no need to use LOWER() on them in the following query.
        $sql = "SELECT ctx.id
                  FROM {enrol_phonepe} ep
                  JOIN {enrol} e ON ep.instanceid = e.id
                  JOIN {context} ctx ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                  JOIN {user} u ON u.id = ep.userid OR LOWER(u.email) = ep.receiver_email OR LOWER(u.email) = ep.business
                 WHERE u.id = :userid";
        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid'        => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        // Values of ep.receiver_email and ep.business are already normalised to lowercase characters by phonepe,
        // therefore there is no need to use LOWER() on them in the following query.
        $sql = "SELECT u.id
                  FROM {enrol_phonepe} ep
                  JOIN {enrol} e ON ep.instanceid = e.id
                  JOIN {user} u ON ep.userid = u.id OR LOWER(u.email) = ep.receiver_email OR LOWER(u.email) = ep.business
                 WHERE e.courseid = :courseid";
        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('id', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Values of ep.receiver_email and ep.business are already normalised to lowercase characters by phonepe,
        // therefore there is no need to use LOWER() on them in the following query.
        $sql = "SELECT ep.*
                  FROM {enrol_phonepe} ep
                  JOIN {enrol} e ON ep.instanceid = e.id
                  JOIN {context} ctx ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                  JOIN {user} u ON u.id = ep.userid OR LOWER(u.email) = ep.receiver_email OR LOWER(u.email) = ep.business
                 WHERE ctx.id {$contextsql} AND u.id = :userid
              ORDER BY e.courseid";

        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid'        => $user->id,
            'emailuserid'   => $user->id,
        ];
        $params += $contextparams;

        // Reference to the course seen in the last iteration of the loop. By comparing this with the current record, and
        // because we know the results are ordered, we know when we've moved to the phonepe transactions for a new course
        // and therefore when we can export the complete data for the last course.
        $lastcourseid = null;

        $strtransactions = get_string('transactions', 'enrol_phonepe');
        $transactions = [];
        $phoneperecords = $DB->get_recordset_sql($sql, $params);
        foreach ($phoneperecords as $phoneperecord) {
            if ($lastcourseid != $phoneperecord->courseid) {
                if (!empty($transactions)) {
                    $coursecontext = \context_course::instance($phoneperecord->courseid);
                    writer::with_context($coursecontext)->export_data(
                            [$strtransactions],
                            (object) ['transactions' => $transactions]
                    );
                }
                $transactions = [];
            }

            $transaction = (object) [
                'receiver_id'         => $phoneperecord->receiver_id,
                'item_name'           => $phoneperecord->item_name,
                'userid'              => $phoneperecord->userid,
                'memo'                => $phoneperecord->memo,
                'tax'                 => $phoneperecord->tax,
                'option_name1'        => $phoneperecord->option_name1,
                'option_selection1_x' => $phoneperecord->option_selection1_x,
                'option_name2'        => $phoneperecord->option_name2,
                'option_selection2_x' => $phoneperecord->option_selection2_x,
                'payment_status'      => $phoneperecord->payment_status,
                'pending_reason'      => $phoneperecord->pending_reason,
                'reason_code'         => $phoneperecord->reason_code,
                'txn_id'              => $phoneperecord->txn_id,
                'parent_txn_id'       => $phoneperecord->parent_txn_id,
                'payment_type'        => $phoneperecord->payment_type,
                'timeupdated'         => \core_privacy\local\request\transform::datetime($phoneperecord->timeupdated),
            ];
            if ($phoneperecord->userid == $user->id) {
                $transaction->userid = $phoneperecord->userid;
            }
            if ($phoneperecord->business == \core_text::strtolower($user->email)) {
                $transaction->business = $phoneperecord->business;
            }
            if ($phoneperecord->receiver_email == \core_text::strtolower($user->email)) {
                $transaction->receiver_email = $phoneperecord->receiver_email;
            }

            $transactions[] = $phoneperecord;

            $lastcourseid = $phoneperecord->courseid;
        }
        $phoneperecords->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($transactions)) {
            $coursecontext = \context_course::instance($phoneperecord->courseid);
            writer::with_context($coursecontext)->export_data(
                    [$strtransactions],
                    (object) ['transactions' => $transactions]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }

        $DB->delete_records('enrol_phonepe', array('courseid' => $context->instanceid));
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        $contexts = $contextlist->get_contexts();
        $courseids = [];
        foreach ($contexts as $context) {
            if ($context instanceof \context_course) {
                $courseids[] = $context->instanceid;
            }
        }

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $select = "userid = :userid AND courseid $insql";
        $params = $inparams + ['userid' => $user->id];
        $DB->delete_records_select('enrol_phonepe', $select, $params);

        // We do not want to delete the payment record when the user is just the receiver of payment.
        // In that case, we just delete the receiver's info from the transaction record.

        $select = "business = :business AND courseid $insql";
        $params = $inparams + ['business' => \core_text::strtolower($user->email)];
        $DB->set_field_select('enrol_phonepe', 'business', '', $select, $params);

        $select = "receiver_email = :receiver_email AND courseid $insql";
        $params = $inparams + ['receiver_email' => \core_text::strtolower($user->email)];
        $DB->set_field_select('enrol_phonepe', 'receiver_email', '', $select, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = ['courseid' => $context->instanceid] + $userparams;

        $select = "courseid = :courseid AND userid $usersql";
        $DB->delete_records_select('enrol_phonepe', $select, $params);

        // We do not want to delete the payment record when the user is just the receiver of payment.
        // In that case, we just delete the receiver's info from the transaction record.

        $select = "courseid = :courseid AND business IN (SELECT LOWER(email) FROM {user} WHERE id $usersql)";
        $DB->set_field_select('enrol_phonepe', 'business', '', $select, $params);

        $select = "courseid = :courseid AND receiver_email IN (SELECT LOWER(email) FROM {user} WHERE id $usersql)";
        $DB->set_field_select('enrol_phonepe', 'receiver_email', '', $select, $params);
    }
}