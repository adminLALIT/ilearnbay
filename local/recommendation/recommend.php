<?php
require_once('../../config.php');

require_login();
$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$return = $CFG->wwwroot . '/local/recommendation/youtube.php';
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/recommendation/domain_mapped.php');
$PAGE->set_title('Course Recommendation');
$PAGE->set_heading('Course Recommendation');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
define("MAX_RESULTS", 20);
$searchvideos = [];
$responsetype = ''; 
$responsemsg =  '';
if (isset($_POST['submit'])) {
    
    $keyword = $_POST['keyword'];
    
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
$data = [
'searchresults' => $searchvideos,
'maxresults' => MAX_RESULTS,
'responsetype' => $responsetype,
'responsemsg' => $responsemsg,
'keyword' => $keyword
];
echo $OUTPUT->render_from_template('local_recommendation/youtubecontent', $data);
echo $OUTPUT->footer();
