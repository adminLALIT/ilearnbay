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
$vimeovideos = [];
$responsetype = '';
$responsemsg =  '';
$keyword = '';
$domainid = 0;
$courseid = '';
$curatorcondition = false;
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
// Return values.
if (is_curator()) {
    $return = 'savedvideos.php';
} else {
    $return = 'recommend.php';
}


if (isset($_POST['submit'])) {
    $domainid = $_POST['domain'];
    $courseid = $_POST['course'];
    $companyid = $DB->get_field('company_course_domain', 'companyid', ['id' => $domainid]);
    // If youtubevideos is coming.
    if (isset($_POST['videos']) || isset($_POST['vimeovideo'])) {
        $submitdata = $_POST;
        $content = new stdClass();
        $content->curatorid = $USER->id;
        $content->companyid = $companyid;
        $content->domain = $domainid;
        $content->course = $courseid;
        $content->time_created = time();
        foreach ($submitdata as $key => $value) {
            $pos =  stripos($key, "ideoid_");
            $vipos =  stripos($key, "cdeoid_");
            if (!empty($pos)) {
                $videoid =  str_replace("videoid_", "", $key);
                $content->contenttype = 'youtube';
                $content->videoid = $videoid;
                $content->videolink = $value;
                $inserted = $DB->insert_record('curator_save_content', $content, $returnid = true, $bulk = false);
            }
            if (!empty($vipos)) {
                $videoid =  str_replace("vcdeoid_", "", $key);
                $content->contenttype = 'vimeo';
                $content->videoid = $videoid;
                $content->videolink = $value;
                $inserted = $DB->insert_record('curator_save_content', $content, $returnid = true, $bulk = false);
            }
        }
        if ($inserted) {
            redirect($return, 'Record saved successfully', null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($return);
        }
    } else {
        $keyword = $DB->get_field('company_course_domain', 'domain', ['id' => $_POST['domain']]);
        if (is_curator()) {
            $getcontenttype = $DB->get_field_sql("SELECT cac.content FROM {curator_assign_course} cac WHERE cac.domain = $domainid AND cac.course = $courseid AND cac.curatoruserid = $USER->id");
        } else {
            $getcontenttype = $DB->get_field_sql("SELECT cac.content FROM {curator_assign_course} cac WHERE cac.domain = $domainid AND cac.course = $courseid");
        }

        if (empty($keyword)) {
            $response = array(
                "type" => "error",
                "message" => get_string('emptykeyword', 'local_recommendation')
            );
            if (!empty($response)) {
                $responsetype =  $response["type"];
                $responsemsg =  $response["message"];
            }
        }

        if (!empty($keyword)) {
            // Get youtube content.
            $getcontent = explode(",", $getcontenttype); // If content contains multiple words.
            // if ($getcontenttype == 'youtube') {
            if ((in_array("youtube", $getcontent))) {

                $response =  get_youtube_content($keyword);
                $data = json_decode($response);
                $value = json_decode(json_encode($data), true);
                for ($i = 0; $i < MAX_RESULTS; $i++) {
                    if (isset($value['items'][$i]['id']['videoId'])) {
                        $videoId = $value['items'][$i]['id']['videoId'];
                        if (is_student()) {
                            if ($DB->record_exists('curator_save_content', ['domain' => $domainid, 'course' => $courseid, 'videoid' => $videoId])) {
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
                        } else {
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
                if (count($searchvideos) == 0) {
                    $response = array(
                        "type" => "error",
                        "message" => get_string('emptyvideos', 'local_recommendation')
                    );
                    if (!empty($response)) {
                        $responsetype =  $response["type"];
                        $responsemsg =  $response["message"];
                    }
                }
            }

            // if ($getcontenttype == 'vimeo') {
            if (in_array("vimeo", $getcontent)) {
                // Get vimeo content.
                $vimeoresponse =  get_vimeo_content($keyword);
                $vimeodata = json_decode($vimeoresponse, true);

                // Check if the response was successful
                if ($vimeodata && isset($vimeodata['data'])) {
                    $videos = $vimeodata['data'];
                    foreach ($videos as $video) {
                        $videoTitle = $video['name'];
                        $videouri = $video['uri'];
                        $videoarr = explode('/', $videouri);
                        $videoid = end($videoarr);
                        if (is_student()) {
                            if ($DB->record_exists('curator_save_content', ['domain' => $domainid, 'course' => $courseid, 'videoid' => $videoid])) {
                                $videodescription = $video['description'];
                                $videodescription =  substr($videodescription, 0, 30) . "...";
                                $iframeurl = $video['player_embed_url'];
                                $vimeovideos[] = [
                                    'videotitle' => $videoTitle,
                                    'description' => $videodescription,
                                    'iframeurl' => $iframeurl,
                                    'videoid' => $videoid,
                                ];
                            }
                        } else {
                            $videodescription = $video['description'];
                            $videodescription =  substr($videodescription, 0, 30) . "...";
                            $iframeurl = $video['player_embed_url'];
                            $vimeovideos[] = [
                                'videotitle' => $videoTitle,
                                'description' => $videodescription,
                                'iframeurl' => $iframeurl,
                                'videoid' => $videoid,
                            ];
                        }
                    }
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
    $curatorcondition = true;
    $assigndomain = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id IN (SELECT domain FROM {curator_assign_course} WHERE curatoruserid = $USER->id)");
} else {
    $assigndomain = $DB->get_records_sql_menu("SELECT id, domain FROM {company_course_domain} WHERE id IN (SELECT domainid FROM {domain_mapping} WHERE profilefield IN (SELECT fieldid FROM {user_info_data} WHERE userid = $USER->id)) OR id IN (SELECT domainid FROM {additional_domains} WHERE studentuserid = $USER->id)");
}
if (!empty($assigndomain)) {
    foreach ($assigndomain as $key => $domainvalue) {
        if ($key == $domainid) {
            $selected = 'selected';
        } else {
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
    'selectcourse' => $selectcourse,
    'curatorcondition' => $curatorcondition,
    'cancelpage' => $return,
    'vimeovideos' => $vimeovideos,

];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_recommendation/youtubecontent', $data);
echo $OUTPUT->footer();
