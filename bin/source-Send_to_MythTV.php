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

$source_param['Notification']['desc'] = 'Notification message [NAME] and [NUMBER] will have the CID Name and Number substituted';
$source_param['Notification']['type'] = 'textarea';
$source_param['Notification']['default'] = 'Incoming call from [NAME]:[NUMBER]';


if($usage_mode == 'post processing')

{
	if (($run_param['IP_address:Port'] !='') && ($first_caller_id != ''))
	{
		$notice = str_replace('[NAME]', $first_caller_id, $run_param['Notification']);
		$notice = str_replace('[NUMBER]', $thenumber_orig, $notice);
		$url="http://".trim($run_param['IP_address'])."/Myth/SendMessage?Message=\"".urlencode($notice)."\"";
		if($debug)
		{
			print 'Send to URL: '.$url.'<br><br>';
		}
		$value = get_url_contents($url);
	}
}
