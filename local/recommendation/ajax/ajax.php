<?php
require_once('../../../config.php');
$companyid = $_POST['companyid'];
if ($companyid) {
    $data = [];
    $html = '<option value="">Select Domain</option>';
    $companydomain = $DB->get_records_sql_menu("SELECT id,domain FROM {company_course_domain} WHERE companyid = $companyid ORDER BY id desc");
    foreach($companydomain as $key => $value) {
        $html .= '<option value="'.$key.'">'.$value.'</option>';
    }
    $json['options'] = $html;
    echo json_encode($json);
}
