<?php
require_once('../../../config.php');
$companyid = $_POST['companyid'];
if ($companyid) {
    $data = [];
    $html = '<option value="">Select Domain</option>';
    $coursehtml = '<option value="">Select Course</option>';
    $companycourse = $DB->get_records_sql_menu("SELECT c.id,c.fullname FROM {course} c JOIN {company_course} cc ON cc.courseid = c.id WHERE cc.companyid = $companyid ORDER BY id desc");
    $companydomain = $DB->get_records_sql_menu("SELECT id,domain FROM {company_course_domain} WHERE companyid = $companyid ORDER BY id desc");
    foreach($companydomain as $key => $value) {
        $html .= '<option value="'.$key.'">'.$value.'</option>';
    }
    foreach($companycourse as $key => $value) {
        $coursehtml .= '<option value="'.$key.'">'.$value.'</option>';
    }
    $json['options'] = $html;
    $json['courseoptions'] = $coursehtml;
    echo json_encode($json);
}
