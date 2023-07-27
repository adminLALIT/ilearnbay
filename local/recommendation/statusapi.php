<?php
$curl = curl_init();
$merchantId = "PGTESTPAYUAT";
$merchantTransactionId = "MT7850590068188104";
$parameters = [
    "merchantId" => $merchantId,
    "merchantTransactionId" => $merchantTransactionId
  ];
// Calculate the checksum
$checksum = hash('sha256', "/pg/v1/status/$merchantId/$merchantTransactionId" . '099eb0cd-02cf-4e2a-8aca-3e6c6aff0399') . "###" . 1;
curl_setopt_array($curl, [
  CURLOPT_URL => "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/$merchantId/$merchantTransactionId",
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
  echo $response;
}