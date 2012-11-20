<?php 
/*	
	Growl Notification Module : Send notifications to multiple hosts
	
	Written By Francois Dechery, aka Soif. https://github.com/soif/,
	
	* Version 1.0, Nov 14, 2012 - Initial Release

	* Requirements: 
		- Php 5.x.x
		- Growl Pear Module (see below)
		- Mac, Windows workstation or iphone, ipad, android phones with a Growl client:
			Mac		: http://growl.info/
			Win		: http://www.growlforwindows.com
			IOS		: http://www.prowlapp.com/
			Android	: https://play.google.com/store/apps/details?id=com.growlforandroid.client

	* Notes: I've only tested this software on mac clients, but I expect it to also 
	work on others clients (using the 'Gntp' mode).

	*Licence: This program is free software; you can redistribute it and/or modify it 
	under the terms of the GNU General Public License as published by the Free Software 
	Foundation; either version 2 of the License, or (at your option) any later version.
*/


// INSTALLATION ##########################################################################
// This module requires the Growl php PEAR module: http://growl.laurent-laville.org/

// To install it, Either: 
// 1) $ pear install Net_Growl
$path_to_growl_autoload='Net/Growl/Autoload.php';

// 2) or you can download it into the bin folder:
// $ cd /var/www/html/admin/modules/superfecta/bin/
// $ wget http://growl.laurent-laville.org/bin/Net_Growl-2.6.0.tgz
// $ tar xvfz Net_Growl-2.6.0.tgz
// then uncomment this:
//$path_to_growl_autoload=dirname(__FILE__).'/Net_Growl-2.6.0/Net/Growl/Autoload.php';

// #######################################################################################


//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to multiple computers.<br>This datasource should be one of the last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.";

$source_param = array();
$source_param['Hosts']['desc'] 			= 'Specify the IPs of hosts to be notified (separated by ",") ';
$source_param['Hosts']['type'] 			= 'text';
$source_param['Hosts']['default'] 		= '10.0.0.1';

$source_param['Display_Setting']['desc'] = 'Number Format';
$source_param['Display_Setting']['type'] = 'select';
$source_param['Display_Setting']['option'][1] = '(132) 456-7890';
$source_param['Display_Setting']['option'][2] = '12 34 56 78 90';
$source_param['Display_Setting']['option'][3] = 'no formatting';
$source_param['Display_Setting']['default'] = 3;

$source_param['Mode']['desc'] 			= 'Growl Protocol';
$source_param['Mode']['type'] 			= 'select';
$source_param['Mode']['option']['udp'] 	= 'Udp (MacOSX only )';
$source_param['Mode']['option']['gntp'] = 'Gntp (MacOSX >=10.7 , Windows )';
$source_param['Mode']['option']['both'] = 'Both';
$source_param['Mode']['default'] 		= 'udp';

$source_param['Priority']['desc'] 		= 'Priority (-2|-1|0|1|2)';
$source_param['Priority']['type'] 		= 'select';
$source_param['Priority']['option'][-2] = 'Low';
$source_param['Priority']['option'][-1] = 'Moderate';
$source_param['Priority']['option'][0] 	= 'Normal';
$source_param['Priority']['option'][1] 	= 'High';
$source_param['Priority']['option'][2] 	= 'Emergency';
$source_param['Priority']['default'] 	= 1;

$source_param['Sticky']['desc'] 		= 'Sticky (0|1)';
$source_param['Sticky']['type'] 		= 'select';
$source_param['Sticky']['option'][0] 	= 'No';
$source_param['Sticky']['option'][1] 	= 'Yes';

$source_param['Application']['desc'] 	= 'Application Name';
$source_param['Application']['type'] 	= 'text';
$source_param['Application']['default'] = 'Pbx Notification';

$source_param['Password']['desc'] 		= 'Password';
$source_param['Password']['type'] 		= 'text';
$source_param['Password']['default'] 	= '';

// Start Processing ----------------------------------------------------------------------
if($usage_mode == 'post processing'){
	$growl_start_time=mctime_float();

	if (($run_param['Hosts'] !='') ){

		// format numbers -------------------------------
		$thenumberformated = $thenumber;
		$the_did_formated =$DID;
		switch ($run_param['Display_Setting']){
			case 1:
				if (strlen($thenumber)==10){
					$thenumberformated	='('.substr($thenumber,0,3).') '.substr($thenumber,3,3).'-'.substr($thenumber,6,4);
				}	
				if (strlen($the_did_formated)==10){
					$the_did_formated	='('.substr($the_did_formated,0,3).') '.substr($the_did_formated,3,3).'-'.substr($the_did_formated,6,4);
				}
				break;
			case 2:
				if (strlen($thenumber)==10){
					$thenumberformated=substr($thenumber,0,2).' '.substr($thenumber,2,2).' '.substr($thenumber,4,2).' '.substr($thenumber,6,2).' '.substr($thenumber,8,2);
				}
				if (strlen($the_did_formated)==10){
					$the_did_formated	=substr($the_did_formated,0,2).' '.substr($the_did_formated,2,2).' '.substr($the_did_formated,4,2).' '.substr($the_did_formated,6,2).' '.substr($the_did_formated,8,2);
				}	
				break;
		}

		// prepare message ----------------------
		$gr_did		=$the_did_formated;
		$gr_name	=htmlspecialchars($first_caller_id);
		$gr_num		=$thenumberformated;
	
		//Growl it -----------------------------
		$growl_timeout				=3;
		$growl_do_register			=true;
		
		$grow_application 			= $run_param['Application'];
		$growl_notification_messages= 'Messages';
		$growl_app_notifications 	= array($growl_notification_messages);
		$growl_app_password 		= $run_param['Password'];
		$growl_mode 				= $run_param['Mode'];

		$growl_title		="Line: $gr_did";
		$growl_message		="$gr_name\n$gr_num";	

		$growl_app_options  = array(
			'protocol'	=> $growl_mode, 
			'timeout'	=> $growl_timeout,
//	optionally (in Gntp Mode) you might include an icon from a local path or remote URL.
//			'AppIcon'  => 'http://www.laurent-laville.org/growl/images/Help.png',
		);
		$growl_options     	= array(
			'priority'	=> $run_param['Priority'],
			'sticky' 	=> (bool) $run_param['Sticky'],
		);
		
		require_once $path_to_growl_autoload;
		if($debug){print 'Sending Growl Notifications:<br>';}

		$growl_hosts=explode(',',$run_param['Hosts']);
		foreach ( $growl_hosts as $growl_host ){
			$growl_host	=trim($growl_host);
			
			if($growl_host){
				
				// first run ----------------------------
				$growl_app_options['host']=$growl_host;
				if($growl_mode=='both'){
					$growl_app_options['protocol']='udp';
				}
				
				if($debug){print "<li><b>{$growl_app_options['protocol']}</b>&nbsp; to $growl_host : ";}
				try {
					
					$growl = Net_Growl::singleton($grow_application, $growl_app_notifications, $growl_app_password, $growl_app_options);
					$growl_do_register and $growl->register();
					$growl->publish($growl_notification_messages, $growl_title, $growl_message,$growl_options);
					$growl->reset();
					if($debug){print "OK</li>\n";}
				}
				catch (Net_Growl_Exception $e) {
					if($debug){print "ERROR= ".$e->getMessage() ."</li>\n";}
				}
				
				//(mode=both) do it again baby ------------------- 
				if($growl_mode=='both'){
					$growl_app_options['protocol']='gntp';
					if($debug){print "<li><b>{$growl_app_options['protocol']}</b> to $growl_host : ";}
					try {
						$growl = Net_Growl::singleton($grow_application, $growl_app_notifications, $growl_app_password, $growl_app_options);
						$growl_do_register and $growl->register();
						$growl->publish($growl_notification_messages, $growl_title, $growl_message,$growl_options);
						$growl->reset();
						if($debug){print "OK</li>\n";}
					}
						catch (Net_Growl_Exception $e) {
						if($debug){print "ERROR= ".$e->getMessage() ."</li>\n";}
					}
				}
			}
		}
		if($debug){
			print "Growl <img src='images/scrollup.gif'> took ".number_format((mctime_float()-$growl_start_time),4)." seconds To notify all hosts.<br>\n<br>\n";
		}
	}
}

?>