<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once('lib.php');


require_login();
global $CFG, $USER, $PAGE;
$receiveddata = $_POST;

$PAGE->set_context(context_system::instance());

$phoneperecord = $DB->get_record('payment_gateways', ['gateway' => 'phonepe', 'enabled' => 1]);

if ($phoneperecord) {
  $phonepesecrets = json_decode($phoneperecord->config);
} else {
  $phonepesecrets = '';
}
$saltkey = $phonepesecrets->saltkey;
$saltindex = $phonepesecrets->saltindex;
if ($phonepesecrets->environment == 'sandbox') {
  $hosturl = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status';
 
} else {
  $hosturl = 'https://api.phonepe.com/apis/hermes/pg/v1/status';
  
}
$curl = curl_init();
$merchantId = $receiveddata['merchantId'];
$merchantTransactionId = $receiveddata['transactionId'];;
$parameters = [
  "merchantId" => $merchantId,
  "merchantTransactionId" => $merchantTransactionId
];
// Calculate the checksum
$checksum = hash('sha256', "/pg/v1/status/$merchantId/$merchantTransactionId" . $saltkey) . "###" . $saltindex;
curl_setopt_array($curl, [
  CURLOPT_URL => "$hosturl/$merchantId/$merchantTransactionId",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "accept: application/json",
    "X-VERIFY: " . $checksum,
    "X-MERCHANT-ID: " . $merchantId,
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $responsedata = json_decode($response);

  if ($responsedata->code == 'PAYMENT_SUCCESS') {

    if (isset($_SESSION['courseid'])) {

      $courseid = $_SESSION['courseid'];
      $enrolid = $_SESSION['enrolid'];
      $accountid = $_SESSION['accountid'];
      $enrol = get_config('enrol_phonepe');
      $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);  // Get course.
      $manager = new course_enrolment_manager($PAGE, $course);
      $instances = $manager->get_enrolment_instances();
      $plugins = $manager->get_enrolment_plugins(true); // Do not allow actions on disabled plugins.
      $instance = $instances[$enrolid];
      $plugin = $plugins[$instance->enrol];
      $today = time();
      if ($instance->enrolstartdate > 0) {
        $timestart = $instance->enrolstartdate;
      } else {
        $timestart = $today;
      }
      $timeend = 0;

      if ($enrol->enrolperiod > 0) {   // Get default duration If enabled for enrolment.
        $duration = $enrol->enrolperiod;
        $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
        $timeend = $timestart + $duration;
      }
      if ($instance->enrolenddate > 0) {
        $timeend = $instance->enrolenddate;
      }
      if ($instance->enrolperiod > 0) {    // Get the current instance duration if available.
        $timeend = $timestart + $instance->enrolperiod;
      }
      $plugin->enrol_user($instance, $USER->id, $enrol->roleid, $timestart, $timeend, null, 0);
      $context = context_course::instance($course->id, MUST_EXIST);

      // Pass $view=true to filter hidden caps if the user cannot see them
      if ($users = get_users_by_capability(
        $context,
        'moodle/course:update',
        'u.*',
        'u.id ASC',
        '',
        '',
        '',
        '',
        false,
        true
      )) {
        $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
      } else {
        $teacher = false;
      }
      $user = $DB->get_record("user", array("id" => $USER->id), "*", MUST_EXIST);
      $phonepeplugin = enrol_get_plugin('phonepe');
      $mailstudents = $phonepeplugin->get_config('mailstudents');
      $mailteachers = $phonepeplugin->get_config('mailteachers');
      $mailadmins   = $phonepeplugin->get_config('mailadmins');
      $phonepebusiness   = $phonepeplugin->get_config('phonepebusiness');
      $shortname = format_string($course->shortname, true, array('context' => $context));

      $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
      if (!empty($mailstudents)) {
        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$USER->id";

        $eventdata = new \core\message\message();
        $eventdata->courseid          = $course->id;
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_phonepe';
        $eventdata->name              = 'phonepe_enrolment';
        $eventdata->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
        $eventdata->userto            = $USER;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
      }

      if (!empty($mailteachers) && !empty($teacher)) {
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);

        $eventdata = new \core\message\message();
        $eventdata->courseid          = $course->id;
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_phonepe';
        $eventdata->name              = 'phonepe_enrolment';
        $eventdata->userfrom          = $user;
        $eventdata->userto            = $teacher;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
      }

      if (!empty($mailadmins)) {
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);
        $admins = get_admins();
        foreach ($admins as $admin) {
          $eventdata = new \core\message\message();
          $eventdata->courseid          = $course->id;
          $eventdata->modulename        = 'moodle';
          $eventdata->component         = 'enrol_phonepe';
          $eventdata->name              = 'phonepe_enrolment';
          $eventdata->userfrom          = $user;
          $eventdata->userto            = $admin;
          $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
          $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
          $eventdata->fullmessageformat = FORMAT_PLAIN;
          $eventdata->fullmessagehtml   = '';
          $eventdata->smallmessage      = '';
          message_send($eventdata);
        }
      }

      // Save the event data.
      $data = $responsedata->data;
      $paymentInstrument = $data->paymentInstrument;
      $record = new stdClass();
      $record->business = $phonepebusiness;
      $record->accountid = $accountid;
      $record->courseid = $courseid;
      $record->userid = $USER->id;
      $record->instanceid = $enrolid;
      $record->roleid = $enrol->roleid;
      $record->amount = $data->amount;  // in paise
      $record->code = $responsedata->code;
      $record->payment_status = $data->state;
      $record->pending_reason = '';
      $record->reason_code = $data->responseCode;
      $record->transactionid = $data->transactionId;
      $record->parent_txn_id = $paymentInstrument->pgTransactionId;
      $record->type = $paymentInstrument->type;
      $record->timeupdated = time();
      $DB->insert_record('enrol_phonepe', $record, $returnid = true, $bulk = false);

      $event = \enrol_phonepe\event\phonepe_event::create(array('context' => context_course::instance($course->id), 'objectid' => $USER->id));
      $event->trigger();
      redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
    }
  }
  else {
    $event = \enrol_phonepe\event\phonepe_event::create(array('context' => context_system::instance(), 'objectid' => $USER->id));
      $event->trigger();
    echo "Payment state : " . $responsedata->code;
    echo "<br>";
    echo $responsedata->message;
  }
};
