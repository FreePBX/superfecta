<?php
// this file is designed to be used as an include that is part of a loop.
// created by lgaetz
// last edited Jan 10, 2013

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the Caller ID Name and Number to MythTV hosts.";
$source_param = array();
$source_param['IP_address:Port']['desc'] = 'Specify MythTV hosts to be notified IP:Port, separate multiple hosts with commas';
$source_param['IP_address:Port']['type'] = 'textarea';
$source_param['IP_address:Port']['default'] = '10.0.0.0:6547';
$source_param['Format_Incomming_Number']['desc'] = 'Specify The Way You Want The Number To Be Displayed';
$source_param['Format_Incomming_Number']['type'] = 'select';
$source_param['Format_Incomming_Number']['option'][1] = 'No Formatting';
$source_param['Format_Incomming_Number']['option'][2] = '(132) 456-7890';
$source_param['Format_Incomming_Number']['option'][3] = '132-456-7890';
$source_param['Format_Incomming_Number']['option'][4] = '12 34 56 78 90';
$source_param['Format_Incomming_Number']['default'] = 1;
$source_param['Notification']['desc'] = 'Notification message [NAME] and [NUMBER] will have the CID Name and Number substituted';
$source_param['Notification']['type'] = 'textarea';
$source_param['Notification']['default'] = 'Incoming call from [NAME]:[NUMBER]';
$source_param['Default_CNAM']['desc'] = 'Text to push if no CNAM is found. Enter the word false to prevent Superfecta from sending anything if no CNAM is found.';
$source_param['Default_CNAM']['type'] = 'text';
$source_param['Default_CNAM']['default'] = "Unknown";

if($usage_mode == 'post processing')
{
	// Determine the CNAM to display
	if ($first_caller_id != "") {
		$myth_cnam = $first_caller_id;
	}
	else if ($run_param['Default_CNAM'] != "false" && $run_param['Default_CNAM'] != "")  {
		$myth_cnam = $run_param['Default_CNAM'];
	}
	else	{
		$myth_cnam = "";
	}

	// format $thenumber for display
	$thenumberformated = $thenumber;
	switch ($run_param['Format_Incomming_Number'])  {
		case 2:
			if (strlen($thenumber)==10)	{
				$thenumberformated='('.substr($thenumber,0,3).') '.substr($thenumber,3,3).'-'.substr($thenumber,6,4);
			}
			if (strlen($thenumber)==11 & substr($thenumber,0,1)==1)  {
				$thenumberformated='1('.substr($thenumber,1,3).') '.substr($thenumber,4,3).'-'.substr($thenumber,7,4);
			}
			break;
		case 3:
			if (strlen($thenumber)==10) {
				$thenumberformated=substr($thenumber,0,3).'-'.substr($thenumber,3,3).'-'.substr($thenumber,6,4);
			}
			if (strlen($thenumber)==11 & substr($thenumber,0,1)==1)  {
				$thenumberformated='1-'.substr($thenumber,1,3).'-'.substr($thenumber,4,3).'-'.substr($thenumber,7,4);
			}
			break;
		case 4:
			if (strlen($thenumber)==10)  {
				$thenumberformated=substr($thenumber,0,2).' '.substr($thenumber,2,2).' '.substr($thenumber,4,2).' '.substr($thenumber,6,2).' '.substr($thenumber,8,2);
			}
			break;
	}
	
	// replace [NAME] and [NUMBER] placeholders with actual values
	$notice = $run_param['Notification'];
	$notice = str_replace('[NAME]',  preg_replace( '/\s+/', ' ', trim($myth_cnam)), $notice);
	$notice = str_replace('[NUMBER]', $thenumberformated, $notice);
	$notice = str_replace('[name]',  preg_replace( '/\s+/', ' ', trim($myth_cnam)), $notice);
	$notice = str_replace('[number]', $thenumberformated, $notice);

	
	if($debug)  {
		print 'MythTV Notice: '.$notice;
	}

	//  break up hosts string into individual hosts and push notice to each
	$mythtv_hosts=explode(',',$run_param['IP_address:Port']);
	$index = 1;
	foreach ( $mythtv_hosts as $mythtv_host )  {
		if ($mythtv_host && $myth_cnam != "")  {
			$url="http://".trim($mythtv_host)."/Frontend/SendMessage?Message=\"".urlencode($notice)."\"";
			if($debug)  {
				print '<br>MythTV host #'.$index.': '.$mythtv_host.'<br>';
			}
		$value = get_url_contents($url);
		$index++;
		}
	}
}
