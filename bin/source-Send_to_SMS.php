<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to any 10-digit SMS phone number using an already-installed Google Voice accout.<br>The message will be sent as a Call notification<br>This datasource should be one of the last data sources on your list.<br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();

$source_param['SMS_number']['desc'] = 'Specify the 10-digit SMS number for delivery of incoming call CLID information.';
$source_param['SMS_number']['type'] = 'number';
$source_param['SMS_number']['default'] = '';
$source_param['GVoice_acctname']['desc'] = 'Specify full Google Voice account to be used for delivery of the SMS message.';
$source_param['GVoice_acctname']['type'] = 'text';
$source_param['GVoice_acctname']['default'] = '@gmail.com';
$source_param['GVoice_acctpass']['desc'] = 'Specify the Google Voice account password.';
$source_param['GVoice_acctpass']['type'] = 'text';
$source_param['GVoice_acctpass']['default'] = '';
$source_param['Default_CNAM']['desc'] = 'Text to push if no CNAM is found. Leave blank to prevent Superfecta from sending anything if no CNAM is found.';
$source_param['Default_CNAM']['type'] = 'text';
$source_param['Default_CNAM']['default'] = '';


if($usage_mode == 'post processing')
{

	$yac_cnam = "";

	if ($first_caller_id != "")
        {
        	$yac_cnam = $first_caller_id;
        }
        else if ($run_param['Default_CNAM'] != "")
        {
		$yac_cnam = $run_param['Default_CNAM'];
        }

	if (($run_param['SMS_number'] !='') && ($run_param['GVoice_acctname'] !='') && ($run_param['GVoice_acctpass'] !='') && ($yac_cnam != ''))
	{
		$yac_text = 'Incoming call: '.$yac_cnam.' '.$thenumber;
//              System(gvoice -e ${GVACCT}@gmail.com -p ${GVPASS} send_sms ${SMS_NOTIFY} "Incoming call: ${var1} ${CALLERID(num)}")
//		shell_exec('/bin/echo -e -n "@CALL'.$yac_text.'"|nc -w 1 '.$run_param['Server_address'].' '.$run_param['Server_TCP_Port'].'');
		shell_exec('/usr/bin/gvoice -e '. $run_param['GVoice_acctname'].' -p '. $run_param['GVoice_acctpass'].' send_sms '.$run_param['SMS_number'].' "'.$yac_text.'" ');

		if($debug)
		{
			print 'Send to SMS: '.$yac_text.'<br><br>';
		}
	}
}
?>
