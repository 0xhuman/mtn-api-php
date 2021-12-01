# MTN Mobile Money API for PHP
A super-simple PHP file that gives you ready-to-go functions for collections and disbursements. 

## Supported Functions
**Collections**
1. [POST/GET Collection Access Token](https://momodeveloper.mtn.com/docs/services/collection/operations/token-POST?) 
2. [POST Request to Pay](https://momodeveloper.mtn.com/docs/services/collection/operations/requesttopay-POST?) 
3. [GET Collection Status](https://momodeveloper.mtn.com/docs/services/collection/operations/requesttopay-referenceId-GET?)

**Disbursements**
1. [GET Disbursement Access Token](https://momodeveloper.mtn.com/docs/services/disbursement/operations/token-POST?)
2. [POST Transfer](https://momodeveloper.mtn.com/docs/services/disbursement/operations/transfer-POST?)
3. [GET Disbursement Status](https://momodeveloper.mtn.com/docs/services/disbursement/operations/transfer-referenceId-GET?)

## How do I use this in my project?
1. Download the repo into your project folder
2. Replace values in the config.php file
3. Add the following to your main php file (or autoload)
```
require_once('mtn.php');
require_once('config.php');
```
4. Call the functions in your code. See testCollect.php for an example usage.

## Need Help?
Email me at celowhale@gmail.com 
