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
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    local_vilt
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require "$CFG->libdir/tablelib.php";
require('lib.php');

require_login();
global $DB, $USER;
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/vilt/catalog.php');
$PAGE->set_heading('My Catalog');
$PAGE->set_title('My Catalog');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js('/local/vilt/amd/src/jquery.js');
$PAGE->requires->js('/local/vilt/amd/src/domainrec.js');

echo $OUTPUT->header();
$companyid = $DB->get_field('company_users', 'companyid', ['userid' => $USER->id]);
// $table = new \local_vilt\catalogs('uniqueid');

// $where = 'vr.companyid =  '.$companyid.' AND (vr.meetingtype = "openuser" OR vr.meetingtype = "all")';
// $field = 'wa.*, c.name as companyname, c.id as companyid,  vr.meetingtype';
// $from = '{webexactivity} wa JOIN {viltrecord} vr ON vr.webexid = wa.id LEFT JOIN {company} c ON c.id = vr.companyid';
// // Work out the sql for the table.
// $table->set_sql($field, $from, $where);
// $table->no_sorting('companyname');
// $table->no_sorting('status');
// $table->define_baseurl("$CFG->wwwroot/local/vilt/catalog.php");

// $table->out(10, true);

// Open user data.
$openuserdata = $DB->get_records_sql("SELECT wa.*, c.name as companyname, c.id as companyid,  vr.meetingtype FROM {webexactivity} wa JOIN {viltrecord} vr ON vr.webexid = wa.id LEFT JOIN {company} c ON c.id = vr.companyid WHERE vr.companyid = $companyid AND vr.meetingtype = 'openuser' ");
$meetingtype = getmeetingtype();
foreach ($openuserdata as $value) {
    $url = new moodle_url('sessionenrolment.php', array('id' => $value->id, 'company' => $value->companyid));
    $buttons[] = html_writer::link($url, 'Sign Up');
    $openuserrecord[] = [
        'meetingtype' => $meetingtype[$value->meetingtype],
        'starttime' => date('Y-m-d H:i:s a', $value->starttime),
        'companyname' => $value->companyname,
        'meetingname' => $value->name,
        'duration' => $value->duration,
        'status' => implode(' ', $buttons),
    ];
}

// All registered User.
// $allregistereddata = $DB->get_records_sql("SELECT wa.*, c.name as companyname, c.id as companyid,  vr.meetingtype FROM {webexactivity} wa JOIN {viltrecord} vr ON vr.webexid = wa.id JOIN {} LEFT JOIN {company} c ON c.id = vr.companyid WHERE vr.companyid = $companyid AND vr.meetingtype = 'openuser'");
// $meetingtype = getmeetingtype();
// foreach ($allregistereddata as $value) {
//     if ($DB->record_exists('')) {
//         # code...
//     }
//     $url = new moodle_url('sessionenrolment.php', array('id' => $value->id, 'company' => $value->companyid));
//     $buttons[] = html_writer::link($url, 'Sign Up');
//     $openuserrecord[] = [
//         'meetingtype' => $meetingtype[$value->meetingtype],
//         'starttime' => date('Y-m-d H:i:s a', $value->starttime),
//         'companyname' => $value->companyname,
//         'meetingname' => $value->name,
//         'duration' => $value->duration,
//         'status' => implode(' ', $buttons),
//     ];
// }


$data = [
    'openuserrecord' => $openuserrecord
];
echo $OUTPUT->render_from_template('local_vilt/catalog', $data);
echo $OUTPUT->footer();
