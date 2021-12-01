<?php 

# Run config file with API credentials
require_once ('config.php');  // replace 'config.php' with your file path
require_once ('mtn.php');

# Generate UUID
$uuid = gen_uuid();

# Set test payment parameters
$amount = 1;
$currency = "ZMW";
$number = substr($phoneNumber, 1);
$number = "260961234567"; 
$timestamp = date('Ymd_Gis');

# Create postDATA array for collection
$REQUEST_BODY = json_encode(array(
'amount' => $amount,
'currency' => $currency,
'externalId' => $timestamp,
'payer' => array(
  'partyIdType' => "MSISDN",'partyId' => $number,),
'payerMessage' => "Payment of K" . $k . " from ".$number,
'payeeNote' => "Click yes to approve",
));

# Get access token from Collections API
mtnTokenCollect();

# Access token success
if ($tokenStatus == "success") {
  
  # Submit collection request
  mtnCollect($uuid, $bearer_token, $REQUEST_BODY);

  # Collection request successfully submitted
  if ($collectStatus == "success") {

    # Check if collection was executed
    mtnCheckCollect($uuid, $bearer_token);

    # Check API gave a response
    if ($checkStatus == "success") {

      # Successful collection!
      if ($withdrawStatus == 1) {

          $finalResult = "success";


      }

      # Failed collection: user cancelled, insufficient funds, or other error
      else {
          $finalResult = "failed: withdrawStatus";
      }


    }

    # Error calling checkCollection API
    else {
          $finalResult = "failed: checkStatus";
    }
  }

  # Failed to submit collection request
  else {
      $finalResult = "failed: collectStatus";

  }



}

# Failed to get access token
else {
    $finalResult = "failed: tokenStatus";
}


echo $finalResult;




?>