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
 * Run the code checker from the web.
 *
 * @package    local_recommendation
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("$CFG->libdir/tablelib.php");
require_once($CFG->dirroot."/local/recommendation/video_table.php");
require_once('lib.php');


require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/recommendation/savedvideos.php');
$PAGE->set_title('Saved Videos');
$PAGE->set_heading('Saved Videos');
$PAGE->set_pagelayout('standard');
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect(new moodle_url('/local/recommendation/savedvideos.php'));
}
if (!is_curator()) {
    throw new moodle_exception(get_string('nopermission', 'local_recommendation', 'core'));
}
echo $OUTPUT->header();

$table = new videos('uniqueid');

$where = 'csc.curatorid = '.$USER->id.'';
$field = 'csc.*, c.name, ccd.domain as domainname, co.fullname as coursename';
$from = "{curator_save_content} csc LEFT JOIN {company} c ON c.id = csc.companyid LEFT JOIN {company_course_domain} ccd ON ccd.id = csc.domain LEFT JOIN {course} co ON co.id = csc.course";
// Work out the sql for the table.
$table->set_sql($field, $from, $where);
$table->define_baseurl("$CFG->wwwroot/local/recommendation/savedvideos.php");
$table->no_sorting('action');

echo html_writer::start_tag('div', ['style' => 'float:right']);

echo $OUTPUT->single_button($CFG->wwwroot . '/local/recommendation/recommend.php', 'Add Videos');
echo html_writer::end_tag('div');
echo "<br>";
echo "<br>";
$table->out(10, true);
echo $OUTPUT->footer();
