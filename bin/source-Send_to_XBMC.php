<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to XBMC.<br>This datasource should be one of the 'last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.<br><br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();
$source_param['URL_Address']['desc'] = 'Specify The IP To The XMBC Installation. (Example: 192.168.0.155)';
$source_param['URL_Address']['type'] = 'text';
$source_param['URL_Address']['default'] = '192.168.0.155';
$source_param['URL_Port']['desc'] = 'Specify The PORT Defined On Your XMBC Installation.  (Example:  8080)';
$source_param['URL_Port']['type'] = 'text';
$source_param['URL_Port']['default'] = '8080';
$source_param['URL_UserName']['desc'] = 'Specify The Username Defined In Your XBMC Installation.  Blank For None.';
$source_param['URL_UserName']['type'] = 'text';
$source_param['URL_UserName']['default'] = '';
$source_param['URL_PassWord']['desc'] = 'Specify The Password Defined In Your XBMC Installation.  Blank For None.';
$source_param['URL_PassWord']['type'] = 'text';
$source_param['URL_PassWord']['default'] = '';
$source_param['Change_Volume']['desc'] = 'Change Volume When Call Comes In, Range Is 100 to 0.  Blank = No Change';
$source_param['Change_Volume']['type'] = 'text';
$source_param['Change_Volume']['default'] = '';
$source_param['Pause_PlayBack']['desc'] = 'Pause Whatever Is Playing When A Call Comes In.';
$source_param['Pause_PlayBack']['type'] = 'checkbox';
$source_param['Pause_PlayBack']['default'] = 'off';
$source_param['Format_Incomming_Number']['desc'] = 'Specify The Way You Want The Number To Be Displayed On Your XMBC';
$source_param['Format_Incomming_Number']['type'] = 'select';
$source_param['Format_Incomming_Number']['option'][1] = '(132) 456-7890';
$source_param['Format_Incomming_Number']['option'][2] = '132-456-7890';
$source_param['Format_Incomming_Number']['option'][3] = '12 34 56 78 90';
$source_param['Format_Incomming_Number']['option'][4] = 'No Formatting';
$source_param['Format_Incomming_Number']['default'] = 4;

if($usage_mode == 'post processing')
{
	if ($run_param['URL_Address'] && $run_param['URL_Port'] != '')
	{
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

		$first_caller_id = str_replace(",", "", $first_caller_id);

		$clidxbmc = urlencode($thenumberformated . ',' . $first_caller_id);

		$url = 'http://';

		if ($run_param['URL_UserName'] && $run_param['URL_PassWord'] != '')
		{
		  $url = $url . $run_param['URL_UserName'] . ':' . $run_param['URL_PassWord'] . '@';
		}

		$url1 = $url . $run_param['URL_Address'] . ':' . $run_param['URL_Port'] . '/xbmcCmds/xbmcHttp?command=SetVolume(' . $run_param['Change_Volume'] . ')';
		$url2 = $url . $run_param['URL_Address'] . ':' . $run_param['URL_Port'] . '/xbmcCmds/xbmcHttp?command=Pause()';
		$url3 = $url . $run_param['URL_Address'] . ':' . $run_param['URL_Port'] . '/xbmcCmds/xbmcHttp?command=ExecBuiltIn&parameter=XBMC.Notification(' . $clidxbmc . ')';

		if($debug)
		{
			if ($run_param['Change_Volume'] != '') { print 'Send to XBMC: ' . $url1 . '<br>'; }
			if ($run_param['Pause_PlayBack'] != 'off') { print 'Send to XMBC: ' . $url2 . '<br>'; }
			print 'Send to XBMC: ' . $url3 . '<br><br>';
		}


		// Send Out The Strings

		if ($run_param['Change_Volume'] !='') { $value = get_url_contents($url1); }
		if ($run_param['Pause_PlayBack'] != 'off') { $value = get_url_contents($url2); }
		$value = get_url_contents($url3);
	}
}
?>
