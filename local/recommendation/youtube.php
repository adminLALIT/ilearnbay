<?php
require_once('../../config.php');

require_login();
$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$return = $CFG->wwwroot.'/local/recommendation/youtube.php';
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/local/recommendation/domain_mapped.php');
$PAGE->set_title('Course Recommendation');
$PAGE->set_heading('Course Recommendation');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
    define("MAX_RESULTS", 20);
    
     if (isset($_POST['submit']) )
     {
        $keyword = $_POST['keyword'];
               
        if (empty($keyword))
        {
            $response = array(
                  "type" => "error",
                  "message" => "Please enter the keyword."
                );
        } 
    }
         
?>
<!doctype html>
<html>
    <head>
        <title>Search Videos by keyword using YouTube Data API V3</title>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

        <style>

            body {
                font-family: Arial;
                /* width: 900px; */
                padding: 10px;
            }
            .search-form-container {
                background: #F0F0F0;
                border: #e0dfdf 1px solid;
                padding: 20px;
                border-radius: 2px;
            }
            .input-row {
                margin-bottom: 20px;
            }
            .input-field {
                width: 100%;
                border-radius: 2px;
                padding: 10px;
                border: #e0dfdf 1px solid;
            }
            .btn-submit {
                padding: 10px 20px;
                background: #333;
                border: #1d1d1d 1px solid;
                color: #f0f0f0;
                font-size: 0.9em;
                width: 100px;
                border-radius: 2px;
                cursor:pointer;
            }
            .videos-data-container {
                background: #F0F0F0;
                border: #e0dfdf 1px solid;
                padding: 20px;
                border-radius: 2px;
            }
            
            .response {
                padding: 10px;
                margin-top: 10px;
                border-radius: 2px;
            }

            .error {
                 background: #fdcdcd;
                 border: #ecc0c1 1px solid;
            }

           .success {
                background: #c5f3c3;
                border: #bbe6ba 1px solid;
            }
            .result-heading {
                margin: 20px 0px;
                padding: 20px 10px 5px 0px;
                border-bottom: #e0dfdf 1px solid;
            }
            iframe {
                border: 0px;
            }
            .video-tile {
                display: inline-block;
                margin: 10px 10px 20px 10px;
            }
            
            .videoDiv {
                width: 250px;
                height: 150px;
                display: inline-block;
            }
            .videoTitle {
                text-overflow: ellipsis;
                white-space: nowrap;
                overflow: hidden;
            }
            
            .videoDesc {
                text-overflow: ellipsis;
                white-space: nowrap;
                overflow: hidden;
            }
            .videoInfo {
                width: 250px;
            }
        </style>
        
    </head>
    <body>
       
        <div class="search-form-container">
            <form id="keywordForm" method="post" action="">
                <div class="input-row">
                    Search Video : <input class="input-field" type="search" id="keyword" name="keyword"  placeholder="Enter Search Video">
                </div>

                <input class="btn-submit"  type="submit" name="submit" value="Search">
            </form>
        </div>
        
        <?php if(!empty($response)) { ?>
                <div class="response <?php echo $response["type"]; ?>"> <?php echo $response["message"]; ?> </div>
        <?php }?>
        <?php
            if (isset($_POST['submit']) )
            {
                
                if (!empty($keyword))
                {
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
            ?>
            <form action="youtubecontent.php" method="post">

            <div class="result-heading">About <?php echo MAX_RESULTS; ?> Results</div>
            <div class="videos-data-container" id="SearchResultsDiv">
            
            <?php

                for ($i = 0; $i < MAX_RESULTS; $i++) {
                        
                        $videoId = $value['items'][$i]['id']['videoId'];
                        if ($videoId) {
                        $title = $value['items'][$i]['snippet']['title'];
                        $description = $value['items'][$i]['snippet']['description'];
                        $videolink = '//www.youtube.com/embed/'.$videoId.'';
                    
                    ?> 
                      
                        <div class="video-tile">
                        <div  class="videoDiv">
                            <iframe id="iframe" style="width:100%;height:100%" src="//www.youtube.com/embed/<?php echo $videoId; ?>" 
                                    data-autoplay-src="//www.youtube.com/embed/<?php echo $videoId; ?>?autoplay=1"></iframe>                     
                        </div>
                        <input type="checkbox" name="<?php echo $videolink; ?>" id="">
                        <div class="videoInfo">
                        <div class="videoTitle"><b><?php echo $title; ?></b></div>
                        <div class="videoDesc"><?php echo $description; ?></div>
                        </div>
                        </div>

                        
           <?php 
                        }
                        $videoId = '';
                    }
                } 
            
            }
            ?> 
            <input type="submit" value="Submit" class="btn btn-primary">
        </div>
            </form>
     
    </body>
</html>
<?php
echo $OUTPUT->footer();
?>