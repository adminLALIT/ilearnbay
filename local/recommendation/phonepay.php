<?php
require_once('../../config.php');
// $phoneperecord = $DB->get_record('payment_gateways', ['gateway' => 'phonepe', 'enabled' => 1]);
// var_dump(get_config('phonepe'));
// die;
if (isset($_POST['amount'])) {
  $customdata = explode("-", $_POST['custom']);
  $_SESSION['courseid'] = (int)$customdata[1];
  $_SESSION['enrolid'] = (int)$customdata[2];
  $curl = curl_init();
  $merchantId = "PGTESTPAYUAT";
  $saltkey = '099eb0cd-02cf-4e2a-8aca-3e6c6aff0399';
  $uniqueId = uniqid();
  // Generate a timestamp
  $timestamp = time();
  // Combine the unique identifier and timestamp
  $transactionId = $uniqueId . "_" . $timestamp;
  // Remove special characters
  $merchantTransactionId = preg_replace('/[^a-zA-Z0-9_]/', '', $transactionId);
  $merchantUserId = "MUID".$uniqueId.time();
  $amount = ((int)$_POST['amount']) * 100;    // in paise
  $callbackUrl = "$CFG->wwwroot/local/recommendation/callback.php";
  $redirectUrl = "$CFG->wwwroot/local/recommendation/callback.php";
  $saltindex = 1;
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
    CURLOPT_URL => "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay",
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

?>