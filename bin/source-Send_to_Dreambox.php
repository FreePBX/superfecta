<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to a dreambox.<br>Enter the URL to the destination dreambox in the format `http://url:port`.<br>This datasource should be one of the last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.<br><br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();
$source_param['URL_address']['desc'] = 'Specify the URL:Port to the dreambox installation. (Example: http://script.somewhere.com:80)';
$source_param['URL_address']['type'] = 'text';
$source_param['URL_address']['default'] = 'http://url:80';
$source_param['Display_Setting']['desc'] = 'Specify the way you want the number to be displayed on your dreambox';
$source_param['Display_Setting']['type'] = 'select';
$source_param['Display_Setting']['option'][1] = '(132) 456-7890';
$source_param['Display_Setting']['option'][2] = '12 34 56 78 90';
$source_param['Display_Setting']['option'][3] = 'no formatting';
$source_param['Display_Setting']['default'] = 3;

if($usage_mode == 'post processing')
{
	if (($run_param['URL_address'] !='') && ($first_caller_id != ''))
	{
		$thenumberformated = $thenumber;	
		switch ($run_param['Display_Setting'])
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
					$thenumberformated=substr($thenumber,0,2).' '.substr($thenumber,2,2).' '.substr($thenumber,4,2).' '.substr($thenumber,6,2).' '.substr($thenumber,8,2);
				}	
				break;
		}

		$cliddreambox=urlencode($first_caller_id.','.$thenumberformated);
		$url=$run_param['URL_address'].'/cgi-bin/message?message='.$cliddreambox;
		if($debug)
		{
			print 'Send to dreambox: '.$run_param['URL_address'].'<br><br>';
		}
		$value = get_url_contents($url);
	}
}
?>
