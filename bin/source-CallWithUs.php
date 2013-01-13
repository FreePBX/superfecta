<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

  // CallWithUs CNAME Lookup by Adam Goldberg, adam_g@yahoo.com
  // -- see https://www.callwithus.com/services
  // -- (indicates $0.006 per lookup)

  // API doucmented at https://www.callwithus.com/API#cnam

  // From the CallWithUS web page footer:
  //
  // Fine print: All prices are final, there are no bogus fees and unfees. Period. Only SIP devices that have 
  // already been created can be connected to sip.callwithus.com to make calls. Please ensure you only use 
  // devices approved by you (Please do not try and connect using two tin cans and a piece of string as we do 
  // not yet support this, but we may support this in the future, the work is in progress and preliminary
  // results are positive). Callwithus.com monthly subscription charge of $0 must be paid in advance and does
  // not include tax of $0 which also must be paid in advance. You will be billed an activation fee of $0
  // plus tax and this must be paid in advance. Calls made incur tax at the rate of 0% each month and must be
  // paid in advance. On cancellation of the service you will be charged a one time disconnection charge of
  // $0. Additional features will be billed at the additional rate of $0 per call. All **YOUR** rights reserved. 

  // Revisions:
  // 0.2 - 2013 Jan 12 - Converted from earlier version
  // 0.3 - 2013 Jan 13 - Added parameterization of username and password

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.

//
$source_desc = "http://callwithus.com   This data source is not free, but provides pretty accurate information.";

$source_param['username']['desc'] = 'Username for CallWithUs, generally a 9-digit number';
$source_param['username']['type'] = 'text';
$source_param['username']['default'] = '';

$source_param['password']['desc'] = 'Password for CallWithUs';
$source_param['password']['type'] = 'text';
$source_param['password']['default'] = '';


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id') {
  if($debug) {
    print 'Searching CallWithUs..<br> ';
  }
  
  $loc_number = $thenumber;
  
  $url = "http://lrn.callwithus.com/api/cnam/index.php?username=".$run_param['username']."&password=".$run_param['password']."&number=" . $loc_number;
  $sresult =  get_url_contents($url);
    
  if($sresult == "-1000" || $sresult == "-1004" || $sresult == "-1005") {
    if($debug) print "CallWithUs lookup error " . $sresult;
    $caller_id = "";
  } else {
    if(strcmp($sresult, "-1003") == 0) {  /* if -1003, not found error */
      /* try it with a leading '1' */
      $url = "http://lrn.callwithus.com/api/cnam/index.php?username=".$run_param['username']."&password=".$run_param['password']."&number=1" . $loc_number;
      $sresult =  get_url_contents($url);
      
      if($sresult == "-1000" || $sresult == "-1004" || $sresult == "-1005") {
	if($debug) print "CallWithUs lookup error (with 1), giving up " . $sresult;
	$sresult="Not Found";	
      }
    }
    
    /* got a good result */
    $caller_id = strip_tags($sresult);
  } 
}
?>
