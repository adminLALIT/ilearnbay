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
require_once('filter_form.php');
require_once("domain_table.php");
require_once("$CFG->dirroot/local/course_completion/lib.php");

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/recommendation/domain_list.php');
$PAGE->set_title('Domain List');
$PAGE->set_heading('Domain List');
$PAGE->set_pagelayout('standard');
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect(new moodle_url('/local/recommendation/domain_list.php'));
}
echo $OUTPUT->header();

$table = new domain_list('uniqueid');
$mform = new domain_list_form();
if ($mform->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form.
} else if ($fromform = $mform->get_data()) {
    if ($fromform->search && $fromform->companyid){

        $where = 'ccd.domain LIKE "%'. $fromform->search .'%" AND c.id = ' . $fromform->companyid . '';
    }
    elseif ($fromform->search) {
        $where = 'ccd.domain LIKE "%'. $fromform->search .'%"';
    }
    elseif ($fromform->companyid) {
        $where = 'c.id = ' . $fromform->companyid . '';
    } else {
        $where = '1=1';
    }
} else {
    if (get_companyid_by_userid($USER->id)) {
        $companyid = get_companyid_by_userid($USER->id);
        $where = 'c.id = ' . $companyid . '';
    }
    else {
        $where = '1=1';
    }
}

$field = 'ccd.*, c.name';
$from = "{company_course_domain} ccd JOIN {company} c ON c.id = ccd.companyid";
// Work out the sql for the table.
$table->set_sql($field, $from, $where);
$table->define_baseurl("$CFG->wwwroot/local/recommendation/domain_list.php");

$table->no_sorting('action');
$mform->display();
echo html_writer::start_tag('div', ['style' => 'float:right']);
if (is_siteadmin()) {
    echo $OUTPUT->single_button($CFG->wwwroot . '/local/recommendation/', 'Add Domain');
}
echo $OUTPUT->single_button($CFG->wwwroot . '/local/recommendation/domain_mapped.php', 'Domain Mapped');
echo $OUTPUT->single_button($CFG->wwwroot . '/local/recommendation/assign_course_list.php', 'Assign Course List');
echo $OUTPUT->single_button($CFG->wwwroot . '/local/recommendation/recommend.php', 'Course Recommendation');
echo html_writer::end_tag('div');
echo "<br>";
echo "<br>";
$table->out(10, true);
echo $OUTPUT->footer();
