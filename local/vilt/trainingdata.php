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
 * @package   local_vilt
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require "$CFG->libdir/tablelib.php";

require_login();

if (!is_siteadmin()) {
throw new moodle_exception(get_string('nopermission', 'local_vilt', 'core'));
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/vilt/trainingdata.php');
$PAGE->set_title('Manage VILT Training');
$PAGE->set_heading('Manage VILT Training');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
echo html_writer::start_tag('div', ['style' => 'float:right']);
echo $OUTPUT->single_button($CFG->wwwroot.'/local/vilt/createvilt.php', 'Create VILT');
echo html_writer::end_tag('div');
echo '<br><br>';

$table = new \local_vilt\viltlist('uniqueid');
$where = '1=1';
$field = 'wa.*';
$from = '{webexactivity} wa';
// Work out the sql for the table.
$table->set_sql($field, $from, $where);
$table->define_baseurl("$CFG->wwwroot/local/vilt/trainingdata.php");
$table->out(10, true);
echo $OUTPUT->footer();
