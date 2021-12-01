<?php

require_once ('config.php');


# Generates UUID  |  No output, call by doing $uuid = gen_uuid();
function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}


#################  Collections  ######################


# Function: Get Collection Access Token  |  Outputs: $tokenStatus, $bearer_token
function mtnTokenCollect() {

  $api_user_and_key  = $c_api_user . ':' . $c_api_key;

  # Basic Authorization
  $basic_auth = "Basic " . base64_encode($api_user_and_key);
  $postData = null;

  // CURL
  $ch = curl_init("https://{$www}.momoapi.mtn.com/collection/token/");
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Authorization: '. $basic_auth,
        'Ocp-Apim-Subscription-Key: '. $c_subscription_key,
        'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => $postData ));

  # Send the request
  $APIresponse = curl_exec($ch);

  # Decode the response
  global $responseData;
  $responseData = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  global $tokenStatus;

  # Check for errors
  if($APIresponse === FALSE){
    die(curl_error($ch)); 
  }

  
  # Successful response
  elseif ($response == 200) {
    $tokenStatus = "success";
    global $bearer_token;
  $bearer_token = 'Bearer ' . $responseData['access_token'];
  }

  # 401 error
  elseif ($response == 401) {
    $tokenStatus = $responseData;
  }

  # Unknown error
  else { echo "TokenC:dang! ";
    $tokenStatus = $responseData;
  }

}


# Function: Post Collection Request |  Outputs: $collectStatus
function mtnCollect($uuid, $bearerToken, $postJSON) {

  $ch = curl_init("https://{$www}.momoapi.mtn.com/collection/v1_0/requesttopay");
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
          'Authorization: '. $bearerToken,
          'X-Reference-Id: ' . $uuid,
          'X-Target-Environment: '. $environment,
          'Ocp-Apim-Subscription-Key: '. $c_subscription_key,
          'Content-Type: application/json'),
        CURLOPT_POSTFIELDS => $postJSON ));

    # Send the request
    $APIresponse = curl_exec($ch);

      # Decode the response
    global $responseData;
    $responseData = json_decode($APIresponse, TRUE);
    global $response;
    $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    global $collectStatus;

    # Check for errors
    if($APIresponse === FALSE){
        die(curl_error($ch)); }

    # Successfully submitted collection request
    elseif($response == 202)
    { $collectStatus = "success"; }

    elseif($response == 400)
    { $collectStatus = $responseData['message'];}

    elseif($response == 409)
    { $collectStatus = $responseData['message']; }

    elseif($response == 500)
    { $collectStatus = $responseData['message']; }

    else { $collectStatus = $responseData['message']; }

}


# Function: Get Collection Status  |  Outputs: $checkStatus, $withdrawStatus, $message
function mtnCheckCollect($uuid, $bearerToken) {
  $ch = curl_init("https://{$www}.momoapi.mtn.com/collection/v1_0/requesttopay/{$uuid}");

    curl_setopt_array($ch, array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
          'Authorization: '. $bearerToken,
          'X-Reference-Id: '. $uuid,
          'X-Target-Environment: '. $environment,
          'Ocp-Apim-Subscription-Key: '. $c_subscription_key,
          'Content-Type: application/string'),
        ));

    # Send the request
    $APIresponse = curl_exec($ch);

    # Decode the response
    global $responseData;
    $responseData = json_decode($APIresponse, TRUE);
    global $response;
    $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    global $withdrawStatus, $message, $checkStatus;

    # Check for errors
    if($APIresponse === FALSE){
        die(curl_error($ch));
        echo "Checkcollect:die";
    }

    # Successfully getting a response
    elseif($response == 200) {

        $checkStatus = "success";

        $i = 0;

        # While the collection status is pending and t < 90sec, keep pinging API every 3 seconds
        # Note: I arbitrarily set the I limit to 30 and ping interval to 3 sec
        while ($responseData['status'] == 'PENDING' && $i < 30){
          
          # Send the request
          $APIresponse = curl_exec($ch);

          # Decode the response
          global $responseData;
          $responseData = json_decode($APIresponse, TRUE);
          $i++;

          # Wait in between API pings
          sleep(3);
        }

        if ($responseData['status'] == 'PENDING'){
            $withdrawStatus = 0;
            $message = "User cancelled or never made the deposit";

        }
        elseif ($responseData['status'] == 'FAILED') {
          $withdrawStatus = 0;
          $message = $responseData['reason'];
        }
        elseif ($responseData['status'] == 'SUCCESSFUL') {
            $withdrawStatus = 1;
            $message = "Collect successful";
        }
    }

    # Error
    elseif($response == 400) { 
      $checkStatus = "Checkcollect:badrequest!400 "; 
    }

    # Error
    elseif($response == 404) { 
      $checkStatus = "Checkcollect:notfound!404 "; 
    }

    # Error
    elseif($response == 500) { 
      $checkStatus = "Checkcollect:internalservererror!500";
    }

    # Error
    else { 
      $checkStatus = "Checkcollect:fudge " .$response;
    }

}




#################  Disbursements  ######################


# Function: Get Disbursement Access Token  |  Outputs: $tokenStatus, $bearer_token
function mtnTokenDisburse() {
  
  # API User
  $api_user = $d_api_user;
  $api_key = $d_api_key;

  $api_user_and_key  = $api_user . ':' . $api_key;

  # Basic Authorization
  $basic_auth = "Basic " . base64_encode($api_user_and_key);
  $postData = null;

  $ch = curl_init("https://{$www}.momoapi.mtn.com/disbursement/token/");
  curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Authorization: '. $basic_auth,
        'Ocp-Apim-Subscription-Key: '. $d_subscription_key,
        'Content-Type: application/json'),
      CURLOPT_POSTFIELDS => $postData ));

  # Send the request
  $APIresponse = curl_exec($ch);

  # Decode the response
  $responseData = json_decode($APIresponse, TRUE);
  global $responseC;
  $responseC = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  global $tokenStatus;

  # Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch)); }

  # Success
  elseif ($responseC == 200) {
    $tokenStatus = "success";
    global $bearer_token;
    $bearer_token = 'Bearer ' . $responseData['access_token'];
  }

  elseif ($responseC == 401) {
    $tokenStatus = "failed";
    global $reason;
    $reason = "401 " . $responseData;
  }

  else { 
    $tokenStatus = "failed"; 
    global $reason;
    $reason = $responseData;
  }

  

}

# Function: Post Disbursement |  Outputs: $disburseStatus
function disburse($postArray, $bearerToken, $uuid){

  $endpoint_url = "https://{$www}.momoapi.mtn.com/disbursement/v1_0/transfer";

  # Parameters
  // $data = array(
  //       "amount" => "1",
  //       "currency" => "ZMW", //default for sandbox
  //       "externalId" => "123456", //reference number
  //       "payee" => array(
  //           "partyIdType" => "MSISDN",
  //           "partyId"     => "260964499767"  //user phone number, these are test numbers)
  //       ),
  //       "payerMessage" => "Funds Transfer",
  //       "payeeNote" => "We have transfered funds"
  //     );

  $data_string = json_encode($postArray);

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $endpoint_url);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

  curl_setopt(
      $curl,
      CURLOPT_HTTPHEADER,
      array(
          'Content-Type: application/json',
          'Authorization: '. $bearerToken, 
          'X-Callback-Url: '. $d_callback_url,
          'X-Reference-Id: '. $uuid,
          'X-Target-Environment: '. $environment,
          'Ocp-Apim-Subscription-Key: '. $d_subscription_key,
      )
  );

  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

  $APIresponse = curl_exec($curl); 
  curl_close($curl);

    // Log response as string so its easier to output
    global $output; 
    $output = $APIresponse;

      // Decode the response
    global $responseData, $http;
    $responseData = json_decode($APIresponse, TRUE);
    $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    global $disburseStatus;

    // Check for errors
    if($APIresponse === FALSE){
        die(curl_error($curl)); $disburseStatus = "fail";}

    elseif($http == 202)
    { $disburseStatus = "success"; }

    else {
      $disburseStatus = "error";
    }

}


# Function: Get Disbursement Status  |  Outputs: $checkStatus, $disburseRestult, $message
function mtnCheckDisburse($uuid, $bearerToken) {

  $ch = curl_init("https://{$www}.momoapi.mtn.com/disbursement/v1_0/transfer/{$uuid}");

  curl_setopt_array($ch, array(
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Authorization: '. $bearerToken,
        'X-Target-Environment: '. $environment,
        'Ocp-Apim-Subscription-Key: '. $d_subscription_key),
    ));
  // Send the request
  $APIresponse = curl_exec($ch);

    // Decode the response
  global $responseDataz;
  $responseDataz = json_decode($APIresponse, TRUE);
  global $response;
  $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  global $checkStatus;

  // Check for errors
  if($APIresponse === FALSE){
      die(curl_error($ch));
      echo "Checkcollect:die";}

  elseif($response == 200) {
      $checkStatus = "success";
      global $disburseResult;

      if ($responseDataz['status'] == FAILED) {
        $disburseResult = "failed";
      }

      elseif ($responseDataz['status'] == PENDING) {
        $disburseResult = "pending";
      }

      else {
        $disburseResult = "pending";
      }

  }

  elseif($response == 400)
  { $checkStatus = "Checkcollect:badrequest!400 "; }

  elseif($response == 404)
  { $checkStatus = "Checkcollect:notfound!404 "; }

  elseif($response == 500)
  { $checkStatus = "Checkcollect:internalservererror!500"; }

  else { $checkStatus = "Checkcollect:fudge " .$response; }

}


?>
