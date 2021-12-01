<?php

# Set Environment
$environment = 'sandbox';  # set to mtnzambia if live

# Set Collections API Credentials
$c_api_user = 'YOUR_COLLECTIONS_API_USER';
$c_api_key = 'YOUR_COLLECTIONS_API_KEY';
$c_subscription_key = 'YOUR_COLLECTIONS_SUBSCRIPTION_KEY';

# Set Disbursement API Credentials
$d_api_user = 'YOUR_DISBURSEMENTS_API_USER';
$d_api_key = 'YOUR_DISBURSEMENTS_API_KEY';
$d_subscription_key = 'YOUR_DISBURSEMENTS_SUBSCRIPTION_KEY';
$d_callback_url = 'YOUR_CALLBACK_URL'; # You must get this whitelisted with the IP by the MTN team


# Set API endpoint URL subdomain based on environment
if ($environment == "sandbox") {
    $www = "sandbox";
  }
  else {
    $www = "proxy";
  }



?>