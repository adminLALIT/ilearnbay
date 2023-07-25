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
 * File containing the step 1 of the upload form.
 *
 * @package    local_vilt
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function getmeetingtype()
{
    $meetingtypes  = [
        'onlyinvited' => 'Only Invited Users',
        'openuser' => 'Open to registered User',
        'all' => 'All registered User',
        'public' => 'Public'
    ];

    return $meetingtypes;
}

function is_companymanager($id)
{
    global $DB, $USER;
    $companymanagerrole = $DB->get_field('role', 'id', ['shortname' => 'companymanager']);
    if (user_has_role_assignment($id, $companymanagerrole)) {
        $companyid = $DB->get_record('company_users', ['userid' => $USER->id]);
        return $companyid->companyid;
    } else {
        return false;
    }
}
