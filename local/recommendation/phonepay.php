<?php

$curl = curl_init();
$merchantId = "MGYAO0E1J";
$merchantTransactionId = "MT7850590068188104";
$merchantUserId = "MU933037302229373";
$merchantOrderId = "MU933037302229373";
$amount = 1;
$callbackUrl = "https://yislms.com/ilearnbay/my/";
$mobileNumber = "8882515026";
$saltindex = 1;
$parameters = [
    "merchantId" => $merchantId,
    "merchantTransactionId" => $merchantTransactionId,
    "merchantUserId" => $merchantUserId,
    "merchantOrderId" => $merchantOrderId,
    "amount" => $amount,
    "callbackUrl" => $callbackUrl,
    "mobileNumber" => $mobileNumber,
    "deviceContext" => [
      "deviceOS" => "ANDROID",
  
    ],
  ];
  $base64Body = base64_encode(json_encode($parameters));
// Calculate the checksum
$checksum = hash('sha256', $base64Body . "/pg/v1/pay" . 'b548ba91-51bf-42ef-9a60-93cfea2fbe64') . "###" . $saltindex;
curl_setopt_array($curl, [
  CURLOPT_URL => "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode($parameters),
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "X-VERIFY: " . $checksum,
    "accept: application/json"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}

?>