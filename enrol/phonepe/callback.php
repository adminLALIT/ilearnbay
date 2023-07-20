<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once('lib.php');


require_login();
global $CFG, $USER, $PAGE;
$receiveddata = $_POST;


$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/enrol/phonepe/callback.php');
$PAGE->set_title("Payment response");
$PAGE->set_heading("Payment response");
$PAGE->navbar->add('Payment response', '');
echo $OUTPUT->header();
$phoneperecord = $DB->get_record('payment_gateways', ['gateway' => 'phonepe', 'enabled' => 1]);
$data = new stdClass();
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
  $data = $responsedata->data;
  $paymentinstrument = $data->paymentInstrument;
  $user = $DB->get_record("user", array("id" => $USER->id), "*", MUST_EXIST);
  $phonepeplugin = enrol_get_plugin('phonepe');
  $mailstudents = $phonepeplugin->get_config('mailstudents');
  $mailteachers = $phonepeplugin->get_config('mailteachers');
  $mailadmins   = $phonepeplugin->get_config('mailadmins');
  $phonepebusiness   = $phonepeplugin->get_config('phonepebusiness');
  $courseid = $_SESSION['courseid'];
  $enrolid = $_SESSION['enrolid'];
  $accountid = $_SESSION['accountid'];
  $userid = $_SESSION['userid'];
  $amount = ($_SESSION['amount']) / 100;
  $enrol = get_config('enrol_phonepe');

  if ($responsedata->code == 'PAYMENT_SUCCESS') {

    if (isset($_SESSION['courseid'])) {
      $plugin_instance = $DB->get_record("enrol", array("id" => $enrolid, "enrol" => "phonepe", "status" => 0), "*", MUST_EXIST);
      // Check that amount paid is the correct amount
      if ((float) $plugin_instance->cost <= 0) {
        $cost = (float) $plugin->get_config('cost');
      } else {
        $cost = (float) $plugin_instance->cost;
      }

      // Use the same rounding of floats as on the enrol form.
      $cost = (format_float($cost, 2, false)) * 100;   // Convert in paise.


      if (($data->amount) < $cost) {
        \enrol_phonepe\util::message_phonepe_error_to_admin("Amount paid is not enough ($data->amount < $cost))", $data);
        die;
      }

      if (!$user = $DB->get_record('user', array('id' => $userid))) {   // Check that user exists
        \enrol_phonepe\util::message_phonepe_error_to_admin("User $userid  doesn't exist", $data);
        die;
      }

      if (!$course = $DB->get_record('course', array('id' => $courseid))) { // Check that course exists
        \enrol_phonepe\util::message_phonepe_error_to_admin("Course $courseid doesn't exist", $data);
        die;
      }
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

      $shortname = format_string($course->shortname, true, array('context' => $context));

      $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

      // Make html table content.
      $html   = '<header style="padding: 10px 0; margin-bottom: 50px; clear: both;">';
      $html   .= '<div style="text-align: center; margin-bottom: 10px;">';
      $html   .= '</div>';
      $html   .= '<h1 style="border-top: 1px solid  #5D6975; border-bottom: 1px solid  #5D6975; color: #5D6975; font-size: 2.4em; line-height: 1.4em; font-weight: normal; text-align: center; margin: 0 0 20px 0;">PAYMENT RECEIPT</h1>';
      $html   .= '<div style="float: right;  text-align: left; clear: both; display: none;">';
      $html   .= '</div>';
      $html   .= '<div style="float: left;">';
      $html   .= '<div style="white-space: nowrap;"><span style="color: #5D6975; text-align: right; width: 80px; margin-right: 10px; display: inline-block; font-size: 0.8em;">NAME</span> ' . $user->firstname . ' ' . $user->lastname . '</div>';
      $html   .= '<div style="white-space: nowrap;"><span style="color: #5D6975; text-align: right; width: 80px; margin-right: 10px; display: inline-block; font-size: 0.8em;">ORDER NO</span> ' . $data->transactionId . '</div>';
      $html   .= '<div style="white-space: nowrap;"><span style="color: #5D6975; text-align: right; width: 80px; margin-right: 10px; display: inline-block; font-size: 0.8em;">EMAIL</span> <a href="mailto:' . $user->email . '">' . $user->email . '</a></div>';
      $html   .= '<div style="white-space: nowrap;"><span style="color: #5D6975; text-align: right; width: 80px; margin-right: 10px; display: inline-block; font-size: 0.8em;">DATE</span>' . date('d-m-Y h:i:s a', time()) . '</div>';
      $html   .= '</div>';
      $html   .= '</header>';
      $html   .= '<div style="clear:both;"></div>';
      $html   .= '<table style="width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px; margin-top: 20px;">';
      $html   .= '<thead>';
      $html   .= '<tr>';
      $html   .= '<th style="text-align: center; padding: 5px 20px; color: #5D6975; border-bottom: 1px solid #C1CED9; white-space: nowrap; font-weight: normal;">COURSE NAME</th>';
      $html   .= '<th style="text-align: center; padding: 5px 20px; color: #5D6975; border-bottom: 1px solid #C1CED9; white-space: nowrap; font-weight: normal;">VALID UNTIL</th>';
      $html   .= '<th style="text-align: center; padding: 5px 20px; color: #5D6975; border-bottom: 1px solid #C1CED9; white-space: nowrap; font-weight: normal;">PRICE</th>';
      $html   .= '<th style="text-align: center; padding: 5px 20px; color: #5D6975; border-bottom: 1px solid #C1CED9; white-space: nowrap; font-weight: normal;">Type</th>';
      $html   .= '<th style="text-align: center; padding: 5px 20px; color: #5D6975; border-bottom: 1px solid #C1CED9; white-space: nowrap; font-weight: normal;">QTY</th>';
      $html   .= '<th style="text-align: center; padding: 5px 20px; color: #5D6975; border-bottom: 1px solid #C1CED9; white-space: nowrap; font-weight: normal;">TOTAL</th>';
      $html   .= '</tr>';
      $html   .= '</thead>';
      $html   .= '<tbody>';
      $html   .= '<tr style="border-bottom: 1px solid #C1CED9;">';
      $html   .= '<td style="text-align: center;">' . $course->fullname . '<div></div> </td>';
      $html   .= '<td style="text-align: center;">' . date("d M Y", $timeend) . '</td>';
      $html   .= '<td style="text-align: center;">INR ' . $amount . ' </td>';
      $html   .= '<td style="text-align: center;">' . $paymentinstrument->type . '</td>';
      $html   .= '<td style="text-align: center;"> 1 </td>';
      $html   .= '<td style="text-align: center;">INR ' . $amount . '</td>';
      $html   .= '</tr>';
      $html   .= '</tbody>';
      $html   .= '</table>';
      $html   .= '<footer style="color: #5D6975; width: 100%; height: 30px; position: absolute; bottom: 0; border-top: 1px solid #C1CED9; padding: 8px 0; text-align: center;">';
      $html   .= 'This is a computer generated receipt of an online payment.';
      $html   .= '</footer>';

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

        $eventdatareceipt = new \core\message\message();
        $eventdatareceipt->courseid          = $course->id;
        $eventdatareceipt->modulename        = 'moodle';
        $eventdatareceipt->component         = 'enrol_phonepe';
        $eventdatareceipt->name              = 'phonepe_enrolment';
        $eventdatareceipt->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
        $eventdatareceipt->userto            = $USER;
        $eventdatareceipt->subject           = get_string("paymentreceipt", 'enrol_phonepe');
        $eventdatareceipt->fullmessage       = get_string('welcometocoursetext', '', $a);
        $eventdatareceipt->fullmessageformat = FORMAT_PLAIN;
        $eventdatareceipt->fullmessagehtml   = $html;
        $eventdatareceipt->smallmessage      = '';
        message_send($eventdatareceipt);
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

        $eventdatareceipt = new \core\message\message();
        $eventdatareceipt->courseid          = $course->id;
        $eventdatareceipt->modulename        = 'moodle';
        $eventdatareceipt->component         = 'enrol_phonepe';
        $eventdatareceipt->name              = 'phonepe_enrolment';
        $eventdatareceipt->userfrom          = $user;
        $eventdatareceipt->userto            = $teacher;
        $eventdatareceipt->subject           = get_string("paymentreceipt", 'enrol_phonepe');
        $eventdatareceipt->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
        $eventdatareceipt->fullmessageformat = FORMAT_PLAIN;
        $eventdatareceipt->fullmessagehtml   = $html;
        $eventdatareceipt->smallmessage      = '';
        message_send($eventdatareceipt);
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

          $eventdatareceipt = new \core\message\message();
          $eventdatareceipt->courseid          = $course->id;
          $eventdatareceipt->modulename        = 'moodle';
          $eventdatareceipt->component         = 'enrol_phonepe';
          $eventdatareceipt->name              = 'phonepe_enrolment';
          $eventdatareceipt->userfrom          = $user;
          $eventdatareceipt->userto            = $admin;
          $eventdatareceipt->subject           = get_string("paymentreceipt", 'enrol_phonepe');
          $eventdatareceipt->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
          $eventdatareceipt->fullmessageformat = FORMAT_PLAIN;
          $eventdatareceipt->fullmessagehtml   = $html;
          $eventdatareceipt->smallmessage      = '';
          message_send($eventdatareceipt);
        }
      }



      $event = \enrol_phonepe\event\phonepe_event::create(array('context' => context_course::instance($course->id), 'objectid' => $USER->id));
      $event->trigger();

      $successMsg = "Your payment of INR " . $amount . " was successfull. Now you should find the courses  
      listed in the courses section in the my homepage. Your transaction id is " . $data->transactionId . ". <br /> Happy Learning. <br /> <a href='" . $CFG->wwwroot . "/'>Click here </a> to go back your Dashboard";
      echo $successMsg;

      $msg = "<p>Your payment was successful</p>
               <p>Transaction ID: {$data->transactionId}</p>";
      // redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
    }
  } else {
    $event = \enrol_phonepe\event\phonepe_event::create(array('context' => context_system::instance(), 'objectid' => $USER->id));
    $event->trigger();
    $msg = "<p>Your payment state $responsedata->code</p>
             <p>{$responsedata->message}</p>";
  }
  echo $msg;
  // Save the event data.

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
  if (isset($data->transactionId)) {
    $record->transactionid = $data->transactionId;
  } else {
    $record->transactionid = 0;
  }
  if (isset($paymentInstrument->pgTransactionId)) {
    $record->parent_txn_id = $paymentInstrument->pgTransactionId;
  } else {
    $record->parent_txn_id = 0;
  }
  //  $record->parent_txn_id = $paymentInstrument->pgTransactionId;
  if (isset($paymentInstrument->type)) {
    $record->type = $paymentInstrument->type;
  } else {
    $record->type = '';
  }
  $record->timeupdated = time();
  $DB->insert_record('enrol_phonepe', $record, $returnid = true, $bulk = false);
  //exit;
  $reurl = $CFG->wwwroot . '/my';

  echo '
      <script>
     setTimeout(function(){
        window.location.href = "' . $reurl . '";
     }, 5000);
  </script>';
  echo $OUTPUT->footer();
};
