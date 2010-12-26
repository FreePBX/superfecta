<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to an URL for external processing.<br>The URL you enter will be sent, with the following appended to it:<br>?thenumber=<i>the CID number</i>&CLID=<i>the CID Name</i>.<br>The destination url must be able to collect and use those arguments.<br>This datasource should be one of the last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.<br><br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();
$source_param['URL_address']['desc'] = 'Specify A URL to send CID/CNAM data to. Use the format `http://url.org`';
$source_param['URL_address']['type'] = 'text';
$source_param['URL_address']['default'] = '';


if($usage_mode == 'post processing')
{
	if (($run_param['URL_address'] !='') && ($first_caller_id != ''))
	{
		$url=$run_param['URL_address'].'?thenumber='.$thenumber.'&CLID='.$first_caller_id;
		if($debug)
		{
			print 'Send to URL: '.$url.'<br><br>';
		}
		$value = get_url_contents($url);
	}
}
?>