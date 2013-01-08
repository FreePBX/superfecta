<?php
// this file is designed to be used as an include that is part of a loop.
// created by lgaetz
// last edited Jan 8, 2013

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to MythTV.";
$source_param = array();
$source_param['IP_address:Port']['desc'] = 'Specify the IP address with port number (probably 6554) of the MythTV system';
$source_param['IP_address:Port']['type'] = 'text';
$source_param['IP_address:Port']['default'] = '';
$source_param['Format_Incomming_Number']['desc'] 		= 'Specify The Way You Want The Number To Be Displayed';
$source_param['Format_Incomming_Number']['type'] 		= 'select';
$source_param['Format_Incomming_Number']['option'][1]	= '(132) 456-7890';
$source_param['Format_Incomming_Number']['option'][2]	= '132-456-7890';
$source_param['Format_Incomming_Number']['option'][3]	= '12 34 56 78 90';
$source_param['Format_Incomming_Number']['option'][4]	= 'No Formatting';
$source_param['Format_Incomming_Number']['default'] = 4;
$source_param['Notification']['desc'] = 'Notification message [NAME] and [NUMBER] will have the CID Name and Number substituted';
$source_param['Notification']['type'] = 'textarea';
$source_param['Notification']['default'] = 'Incoming call from [NAME]:[NUMBER]';


if($usage_mode == 'post processing')
{
	if (($run_param['IP_address:Port'] !='') && ($first_caller_id != ''))
	{

		//format number --------------------------
		$thenumberformated = $thenumber;
		switch ($run_param['Format_Incomming_Number'])
		{
			case 1:
				if (strlen($thenumber)==10)
				{
					$thenumberformated='('.substr($thenumber,0,3).') '.substr($thenumber,3,3).'-'.substr($thenumber,6,4);
				}
				break;
			case 2:
				if (strlen($thenumber)==10)
				{
					$thenumberformated=substr($thenumber,0,3).'-'.substr($thenumber,3,3).'-'.substr($thenumber,6,4);
				}
				break;
			case 3:
				if (strlen($thenumber)==10)
				{
					$thenumberformated=substr($thenumber,0,2).' '.substr($thenumber,2,2).' '.substr($thenumber,4,2).' '.substr($thenumber,6,2).' '.substr($thenumber,8,2);
				}
				break;
		}
	
		$notice = str_replace('[NAME]', $first_caller_id, $run_param['Notification']);
		$notice = str_replace('[NUMBER]', $thenumberformated, $notice);
		$url="http://".trim($run_param['IP_address'])."/Myth/SendMessage?Message=\"".urlencode($notice)."\"";
		if($debug)
		{
			print 'Send to URL: '.$url.'<br><br>';
		}
		$value = get_url_contents($url);
	}
}
