<?php
/*
	######################################################################################
	XBMC Notification Module : Send notifications to multiple XBMC devices
	######################################################################################
	
	Version 2.0	

	* Version History:
	 	(2.0) Dec 4, 2012, Written By Francois Dechery, aka Soif 
			- Frodo compatibility + Old XBMC versions support 
			- Multiple hosts support, 
			- Enhanced display,
			- Time parameter,
			- No more crash when xbmc device is off
	 	
	 	(1.0) Written by ????
			- initial release

	* Notes: Successfully tested on Linux Dharma and Raspbmc Frodo 

	* Licence: This program is free software; you can redistribute it and/or modify it 
	under the terms of the GNU General Public License as published by the Free Software 
	Foundation; either version 2 of the License, or (at your option) any later version.

	######################################################################################
*/

// variables
$my_anonymous_title	="Unknown";	// Title displayed for calls without  a Caller Id name
$my_host_timeout	=0.5; 		//time (in seconds) to wait for each host before it is considered off (should be short)

// 	######################################################################################


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to XBMC.<br>This datasource should be one of the 'last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.<br><br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();

$source_param['Hosts']['desc']		= 'Specify The IP(s) or hostname(s) of the XMBC device(s). (ie: 192.168.0.155). Separate multiple host by commas (ie: tv.local,tv2.local,10.0.0.3 )';
$source_param['Hosts']['type']		= 'text';
$source_param['Hosts']['default']	= '192.168.0.155';

$source_param['Port']['desc']		= 'Specify The PORT Defined On Your XMBC Installation.  (Example:  8080)';
$source_param['Port']['type'] 		= 'text';
$source_param['Port']['default']	= '8080';

$source_param['UserName']['desc']	= 'Specify The Username Defined In Your XBMC Installation.  Blank For None.';
$source_param['UserName']['type']	= 'text';
$source_param['UserName']['default']= '';

$source_param['PassWord']['desc'] 	= 'Specify The Password Defined In Your XBMC Installation.  Blank For None.';
$source_param['PassWord']['type'] 	= 'text';
$source_param['PassWord']['default']= '';

$source_param['Mode']['desc'] 		= 'Select Deprecated mode or json mode.';
$source_param['Mode']['type'] 		= 'select';
$source_param['Mode']['default']	= '5';
$source_param['Mode']['option']['old']	= 'Old API : For XBMC up to v11';
$source_param['Mode']['option']['json']	= 'Json RPC: For XBMC v12 (Frodo)';

$source_param['Change_Volume']['desc']		= 'Change Volume When Call Comes In, Range Is 100 to 0.  Blank = No Change';
$source_param['Change_Volume']['type']		= 'text';
$source_param['Change_Volume']['default']	= '';

$source_param['Pause_PlayBack']['desc']		= 'Pause Whatever Is Playing When A Call Comes In.';
$source_param['Pause_PlayBack']['type']		= 'checkbox';
$source_param['Pause_PlayBack']['default']	= 'off';

$source_param['Notification_Time']['desc'] 		= 'Notification display time (0 to disable)';
$source_param['Notification_Time']['type'] 		= 'text';
$source_param['Notification_Time']['default']	= '5';

$source_param['Format_Incomming_Number']['desc'] 		= 'Specify The Way You Want The Number To Be Displayed On Your XMBC';
$source_param['Format_Incomming_Number']['type'] 		= 'select';
$source_param['Format_Incomming_Number']['option'][1]	= '(132) 456-7890';
$source_param['Format_Incomming_Number']['option'][2]	= '132-456-7890';
$source_param['Format_Incomming_Number']['option'][3]	= '12 34 56 78 90';
$source_param['Format_Incomming_Number']['option'][4]	= 'No Formatting';
$source_param['Format_Incomming_Number']['default'] = 4;

if($usage_mode == 'post processing')
{
	if ($run_param['Hosts'])
	{

		//make sure function has not been declared before
		if(!function_exists("_myXbmcUrl")){
		
			function _myXbmcUrl($array){
				$request=$array;
				$request['jsonrpc']="2.0";
				//$request['method']="Player.PlayPause";
			
				$json=json_encode($request);
				$url='/jsonrpc?request='.urlencode($json);
				return $url;
			}
		}
		
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

		$first_caller_id = str_replace(",", "", $first_caller_id);

		//make notification --------------		
		if($first_caller_id){
			$my_xbmc_title	=$first_caller_id;
			$my_xbmc_mess	=$thenumberformated;
		}
		else{
			$my_xbmc_title	=$thenumberformated;
			$my_xbmc_mess	=$my_anonymous_title;
		}

		$my_xbmc_time	=$run_param['Notification_Time']*1000;
		$my_xbmc_icon	="DefaultActor.png"; // relative to /usr/share/xbmc/addons/skin.confluence/media  or absolute
		
		$my_dharma_params.="$my_xbmc_title,$my_xbmc_mess,$my_xbmc_time,$my_xbmc_icon";
		$my_dharma_params = urlencode($my_dharma_params);

		//hosts loop -----------------------------
		$hosts=explode(',',$run_param['Hosts']);
		foreach ($hosts as $host){
			$host=trim($host);
				
			//make url --------------
			$url = 'http://';
			if ($run_param['UserName'] && $run_param['PassWord'] != ''){
			  $url .= $run_param['UserName'] . ':' . $run_param['PassWord'] . '@';
			}
			$url .= $host . ':' . $run_param['Port'];
	
			// make url params
			if($run_param['Mode']=='json'){
				$my_request=array(
					'method'=>'Application.SetVolume',
					'params'=>array(
						'volume'=>$run_param['Change_Volume']
					)
				);
				$url1 = $url . _myXbmcUrl($my_request);
				
				$my_request=array(
					'method'=>'Player.PlayPause',
					'params'=>array(
						'play'=>'pause'
					)
				);
				$url2 = $url . _myXbmcUrl($my_request);
				
				$my_request=array(
					'method'=>'GUI.ShowNotification',	
					'params'=>array(
						'title'			=>$my_xbmc_title,
						'message'		=>$my_xbmc_mess,
						'image'			=>$my_xbmc_icon,
						'displaytime'	=>$my_xbmc_time
						)
				);
				$url3 = $url . _myXbmcUrl($my_request);
			}
			else{
				$url1 = $url . '/xbmcCmds/xbmcHttp?command=SetVolume(' . $run_param['Change_Volume'] . ')';
				$url2 = $url . '/xbmcCmds/xbmcHttp?command=Pause()';
				$url3 = $url . '/xbmcCmds/xbmcHttp?command=ExecBuiltIn&parameter=XBMC.Notification(' . $my_dharma_params . ')';			
			}

			//debug ------------------------
			if($debug){
				print "Sent Xbmc command To Host: $host <br>\n";
				if ($run_param['Change_Volume']		!= '') 		{ print 'Volume: ' . $url1 . '<br>'; }
				if ($run_param['Pause_PlayBack']	!= 'off')	{ print 'Pause : ' . $url2 . '<br>'; }
				if ($run_param['Notification_Time'] )			{ print 'Notif : ' . $url3 . '<br>'; }
				print '<br>';
			}
	
			// Send Out The Strings if host is online (else bug) -------------------
			if($fp = @fsockopen($host,80,$errCode,$errStr,$my_host_timeout)){   
				if ($run_param['Change_Volume'] !='') 		{ $value = get_url_contents($url1); }
				if ($run_param['Pause_PlayBack'] != 'off')	{ $value = get_url_contents($url2); }
				if ($run_param['Notification_Time'])		{ $value = get_url_contents($url3); }
			}
			@fclose($fp);
		}
	}
}
?>