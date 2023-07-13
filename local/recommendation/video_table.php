<?php

/**
 * team_list_form class to be put in team_list_form.php of root of Moodle installation.
 *  for defining some custom column names and proccessing.
 */
class videos extends table_sql
{
    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array('name', 'domainname', 'coursename', 'videolink', 'action');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array('Company', 'Domain', 'Course', 'Video', 'Action');
        $this->define_headers($headers);
    }
    /**
     * This function is called for each data row to allow processing of the
     * profilefield value.
     *
     * @param object $values Contains object with all the values of record.
     */
    function col_videolink($values)
    {
        global $DB;
        if ($values->contenttype == 'youtube') {
            $video =   html_writer::div("".html_writer::div("".html_writer::start_tag('iframe', ['id' =>'iframe', 'style' => 'width:100%;height:100%', 'src' => '//www.youtube.com/embed/'.$values->videoid.'', 'data-autoplay-src' =>'//www.youtube.com/embed/'.$values->videoid.'?autoplay=1']).html_writer::end_tag('iframe'), 'videoDiv'), 'video-tile');
        }
        else {
            $video = html_writer::div("".html_writer::div("".html_writer::start_tag('iframe', ['id' =>'iframe', 'style' => 'width:100%;height:100%', 'src' => ''.$values->videolink.'', 'data-autoplay-src' =>''.$values->videolink.'?autoplay=1']).html_writer::end_tag('iframe'), 'videoDiv'), 'video-tile');
        }
        return $video;
    }

    /**
     * This function is called for each data row to allow processing of the
     * action value.
     *
     * @param object $values Contains object with all the values of record.
     */

    function col_action($values)
    {
        global $CFG, $DB, $OUTPUT;
        $baseurl = new moodle_url('/local/recommendation/savedvideos.php');
        $url = new moodle_url('recommend.php', array('delete' => 1, 'id' => $values->id, 'returnurl' => $baseurl));
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'Delete'));
        return implode(' ', $buttons);
    }
}
