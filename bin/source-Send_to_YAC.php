<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to a YAC (Yet Another Caller ID Program) service for external processing.<br>The message will be sent as a Call notification<br>This datasource should be one of the last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.<br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();
$source_param['Server_address']['desc'] = 'Specify the server IP or domain name address to call after a CLID was found.';
$source_param['Server_address']['type'] = 'text';
$source_param['Server_address']['default'] = '';
$source_param['Server_TCP_Port']['desc'] = 'Specify the TCP port to be used (default for YAC is 10629).';
$source_param['Server_TCP_Port']['type'] = 'number';
$source_param['Server_TCP_Port']['default'] = '10629';


if($usage_mode == 'post processing')
{
	if (($run_param['Server_address'] !='') && ($first_caller_id != ''))
	{
    	$yac_text = $first_caller_id.'~'.$thenumber; 
		shell_exec('/bin/echo -e -n "@CALL'.$yac_text.'"|nc -w 1 '.$run_param['Server_address'].' '.$run_param['Server_TCP_Port'].'');
		
		if($debug)
		{
			print 'Send to YAC: '.$first_caller_id.' '.$thenumber.'<br><br>';
		}
	}
}
?>