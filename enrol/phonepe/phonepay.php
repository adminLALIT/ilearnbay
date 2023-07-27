<?php
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

// phonepe does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler(\enrol_phonepe\util::get_exception_handler());
$plugin = enrol_get_plugin('phonepe');
// Make sure we are enabled in the first place.
if (!enrol_is_enabled('phonepe')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_phonepe');
}
/// Keep out casual intruders
if (empty($_POST) or !empty($_GET)) {
  http_response_code(400);
  throw new moodle_exception('invalidrequest', 'core_error');
}

$phoneperecord = $DB->get_record('payment_gateways', ['gateway' => 'phonepe', 'enabled' => 1]);
if ($phoneperecord) {
 $phonepesecrets = json_decode($phoneperecord->config);
}
else {
  $phonepesecrets = '';
}

if (isset($_POST['amount'])) {
  $amount = ((int)$_POST['amount']) * 100;    // in paise
  if ($phonepesecrets->environment == 'sandbox') {
    $hosturl = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay';
  }
  else {
    $hosturl = 'https://api.phonepe.com/apis/hermes/pg/v1/pay';
  }
  $customdata = explode("-", $_POST['custom']);
  if (empty($customdata) || count($customdata) < 3) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid value of the request param: custom');
}
$data = new stdClass();
$userid = (int)$customdata[0];
unset($_SESSION['courseid']);
unset($_SESSION['enrolid']);
unset($_SESSION['amount']);
unset($_SESSION['currency_code']);
unset($_SESSION['userid']);

$courseid = $_SESSION['courseid'] = (int)$customdata[1];
$enrolid = $_SESSION['enrolid'] = (int)$customdata[2];
$_SESSION['accountid'] = (int)$phoneperecord->accountid;
$_SESSION['amount'] = $amount;
$_SESSION['userid'] = $USER->id;

$currencycode =  $_POST['currency_code'];

$plugin_instance = $DB->get_record("enrol", array("id" => $enrolid, "enrol" => "phonepe", "status" => 0), "*", MUST_EXIST);

// If currency is incorrectly set then someone maybe trying to cheat the system.
if ($currencycode != $plugin_instance->currency) {
  \enrol_phonepe\util::message_phonepe_error_to_admin(
      "Currency does not match course settings, received: ".$currencycode,
      $data);
  die;
}

  // Check that amount paid is the correct amount
  if ( (float) $plugin_instance->cost <= 0 ) {
    $cost = (float) $plugin->get_config('cost');
} else {
    $cost = (float) $plugin_instance->cost;
}

// Use the same rounding of floats as on the enrol form.
$cost = format_float($cost, 2, false);

if ($amount < $cost) {
  \enrol_phonepe\util::message_phonepe_error_to_admin("Amount paid is not enough ($amount < $cost))", $data);
  die;

}

if (!$user = $DB->get_record('user', array('id'=>$userid))) {   // Check that user exists
  \enrol_phonepe\util::message_phonepe_error_to_admin("User $userid  doesn't exist", $data);
  die;
}

if (!$course = $DB->get_record('course', array('id'=>$courseid))) { // Check that course exists
  \enrol_phonepe\util::message_phonepe_error_to_admin("Course $courseid doesn't exist", $data);
  die;
}

  $curl = curl_init();
  $merchantId = $phonepesecrets->merchantid;
  $saltkey = $phonepesecrets->saltkey;
  $saltindex = $phonepesecrets->saltindex;
  $uniqueId = uniqid();
  // Generate a timestamp
  $timestamp = time();
  // Combine the unique identifier and timestamp
  $transactionId = $uniqueId . "_" . $timestamp;
  // Remove special characters
  $merchantTransactionId = preg_replace('/[^a-zA-Z0-9_]/', '', $transactionId);
  $merchantUserId = "MUID".$uniqueId.time();
  // $indiancurrency = convertCurrency($_POST['currency_code'], 'INR', (int)$_POST['amount']);
  // $amount = ((int)$indiancurrency['converted_amount']) * 100;    // in paise
 
  $callbackUrl = "$CFG->wwwroot/enrol/phonepe/callback.php";
  $redirectUrl = "$CFG->wwwroot/enrol/phonepe/callback.php";
  
  $parameters = [
      "merchantId" => $merchantId,
      "merchantTransactionId" => $merchantTransactionId,
      "merchantUserId" => $merchantUserId,
      "amount" => $amount,   
      "redirectUrl" => $redirectUrl,
      "redirectMode" => "POST",
      "callbackUrl" => $callbackUrl,
      "paymentInstrument" => [
        "type" => "PAY_PAGE",
          ]
    ];
    $base64Body = base64_encode(json_encode($parameters));
  // Calculate the checksum
  $checksum = hash('sha256', $base64Body . "/pg/v1/pay" . $saltkey) . "###" . $saltindex;
  
  curl_setopt_array($curl, [
    CURLOPT_URL => $hosturl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode(['request'=> $base64Body]),
    CURLOPT_HTTPHEADER => [
      "Content-Type: application/json",
      "X-VERIFY: $checksum",
      "accept: application/json"
    ]
  ]);
  
  
  $response = curl_exec($curl);
 
  $err = curl_error($curl);
  
  curl_close($curl);
  
  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
  //   echo $response;
    if ($response) {
      $data = json_decode($response);
    
     if ($data->success == true) {
      if ($data->data->instrumentResponse->redirectInfo->url) {
          $redirectUrl = $data->data->instrumentResponse->redirectInfo->url;
         return redirect($redirectUrl);
      }
     }
   
    }
  }
}
