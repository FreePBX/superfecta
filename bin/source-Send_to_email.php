<?php
//this file is designed to be used as an include that is part of a loop.
//available variables for use are: $thenumber
//last edited Sept 25, 2012 by lgaetz

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the CID and CNAM in the subject to the user supplied email address.";
$source_param = array();

$source_param['Email_address']['desc'] = 'Specify email address';
$source_param['Email_address']['type'] = 'text';
$source_param['Email_address']['default'] = '';
$source_param['Message_Subject']['desc'] = 'Content for the subject of the email.  Substitute [NUMBER] and [NAME] for CID variables.';
$source_param['Message_Subject']['type'] = 'text';
$source_param['Message_Subject']['default'] = 'Incoming call: [NAME] [NUMBER]';
$source_param['Message_Body']['desc'] = 'Content for the body of the email. Substitute [NUMBER] and [NAME] for CID variables.';
$source_param['Message_Body']['type'] = 'text';
$source_param['Message_Body']['default'] = 'Incoming call: [NAME] [NUMBER]';
$source_param['Default_CNAM']['desc'] = 'Text to push if no CNAM is found. Leave blank to prevent Superfecta from sending anything if no CNAM is found.';
$source_param['Default_CNAM']['type'] = 'text';
$source_param['Default_CNAM']['default'] = '';


if($usage_mode == 'post processing')
{

	$cnam = "";

	if ($first_caller_id != "")
        {
        	$cnam = $first_caller_id;
        }
        else if ($run_param['Default_CNAM'] != "")
        {
		$cnam = $run_param['Default_CNAM'];
        }

	if (($run_param['Email_address'] !='') && ($cnam != ''))
	{
		$subject = $run_param['Message_Subject'];
		$subject = str_replace('[NAME]', $cnam, $subject);
		$subject = str_replace('[NUMBER]', $thenumber_orig, $subject);
		$body = $run_param['Message_Body'];
		$body = str_replace('[NAME]', $cnam, $body);
		$body = str_replace('[NUMBER]', $thenumber_orig, $body);
//		$subject = 'Incoming call: '.$cnam.' '.$thenumber.'';
//		shell_exec('mail -s '.$subject.' '.$run_param['Email_address'].' < /dev/null');
		mail ($run_param['Email_address'], $subject, $body);
		if($debug)
		{
			print 'Send to '.$run_param['Email_address'].': '.$subject.'<br><br>';
		}
	}
}
