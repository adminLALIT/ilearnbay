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

require_once('../../config.php');
require_once('lib.php');

require_login();
$id = optional_param('id', 0, PARAM_INT);
$return = new moodle_url('/local/recommendation/savedvideos.php');
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$context = context_system::instance();
$return = $CFG->wwwroot . '/local/recommendation/youtube.php';
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/recommendation/domain_mapped.php');
$PAGE->set_title('Course Recommendation');
$PAGE->set_heading('Course Recommendation');
$PAGE->set_pagelayout('admin');

if (!is_curator() && !is_student()) {
    throw new moodle_exception(get_string('nopermission', 'local_recommendation', 'core'));
}

define("MAX_RESULTS", 20);
$searchvideos = [];
$responsetype = ''; 
$responsemsg =  '';
$keyword = '';
$domainid = 0;
$courseid = '';

if ($delete && $id) {

    if ($confirm && confirm_sesskey()) {
        // Delete existing files first.
        $DB->delete_records('curator_save_content', ['id' => $id]);
        redirect($returnurl);
    }
    $strheading = 'Delete this content';
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);
    $yesurl = new moodle_url('/local/recommendation/recommend.php', array(
        'id' => $id, 'delete' => 1,
        'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl
    ));
    $message = "Do you really want to delete content?";
    echo $OUTPUT->confirm($message, $yesurl, $returnurl);
    echo $OUTPUT->footer();
    die;
}

if (isset($_POST['submit'])) {
    $domainid = $_POST['domain'];
    $courseid = $_POST['course'];
    $companyid = $DB->get_field('company_course_domain', 'companyid', ['id' => $domainid]);
    // If videos is coming.
    if (isset($_POST['videos'])) {
        $submitdata = $_POST;
        $content = new stdClass();
        $content->curatorid = $USER->id;
        $content->companyid = $companyid;
        $content->domain = $domainid;
        $content->course = $courseid;
        $content->time_created = time();
        foreach ($submitdata as $key => $value) {
           $pos =  stripos($key, "ideoid_");
           if (!empty($pos)) {
               $videoid =  str_replace("videoid_","", $key);
               $content->videoid = $videoid;
               $content->videolink = $value;
                $inserted = $DB->insert_record('curator_save_content', $content, $returnid=true, $bulk=false);
           }
         
        }
        if ($inserted) {
           redirect('recommend.php', 'Record saved successfully', null, \core\output\notification::NOTIFY_SUCCESS);
        }
        else {
            redirect('recommend.php');
        }
      
    }
    else {
        $keyword = $DB->get_field('company_course_domain', 'domain', ['id' => $_POST['domain']]);
        if (empty($keyword)) {
            $response = array(
                "type" => "error",
                "message" => "Please enter the keyword."
            );
            if (!empty($response)) {
                $responsetype =  $response["type"]; 
                $responsemsg =  $response["message"];
             }
        }
    
        if (!empty($keyword)) {
            $apikey = get_config('youtube', 'apikey');
            $googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' . $keyword . '&maxResults=' . MAX_RESULTS . '&key=' . $apikey;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            
            curl_close($ch);
            $data = json_decode($response);
            $value = json_decode(json_encode($data), true);
            
            for ($i = 0; $i < MAX_RESULTS; $i++) {
                if (isset($value['items'][$i]['id']['videoId'])) {
                    $videoId = $value['items'][$i]['id']['videoId'];
                    $title = $value['items'][$i]['snippet']['title'];
                    $description = $value['items'][$i]['snippet']['description'];
                    $videolink = '//www.youtube.com/embed/' . $videoId . '';
    
                    $searchvideos[] = [
                        'videoid' => $videoId,
                        'videolink' => $videolink,
                        'description' => $description,
                        'title' => $title,
                    ];
                }
            
            }
        }
    }

}
$selectcourse = [];
// Get submit course.
if (!empty($courseid)) {
    $course = $DB->get_record_sql("SELECT id, fullname FROM {course} WHERE id = $courseid");
    $selectcourse[] = [
        'id' => $course->id,
        'fullname' => $course->fullname
    ];
}

// Get assign courses and domains.
$domains = [];
if (is_curator()) {
    $assigndomain = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id IN (SELECT domain FROM {curator_assign_course} WHERE curatoruserid = $USER->id)");
}
else {
    $assigndomain = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id IN (SELECT domainid FROM {domain_mapping} WHERE profilefield IN (SELECT fieldid FROM {user_info_data} WHERE userid = $USER->id))");
}
if (!empty($assigndomain)) {
    foreach($assigndomain as $key => $domainvalue){
        if ($key == $domainid) {
           $selected = 'selected';
        }
        else {
            $selected = '';
        }
        $domains[] = [
            'id' => $key,
            'domainname' => $domainvalue,
            'selected' => $selected
        ];
    }
}
$data = [
'searchresults' => $searchvideos,
'maxresults' => MAX_RESULTS,
'responsetype' => $responsetype,
'responsemsg' => $responsemsg,
'keyword' => $keyword,
'domains' => $domains,
'selectcourse' => $selectcourse
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_recommendation/youtubecontent', $data);
echo $OUTPUT->footer();
