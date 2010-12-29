<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://whocalled.us - These listings are provided by other users of the whocalled service. This service requires authentication - which you configure in Who Called Extended Information, below.";
$source_param = array();
$source_param['Username']['desc'] = 'Your user account Login on the whocalled.us web site.';
$source_param['Username']['type'] = 'text';
$source_param['Password']['desc'] = 'Your user account Password on the whocalled.us web site.';
$source_param['Password']['type'] = 'password';
$source_param['Get_Caller_ID_Name']['desc'] = 'Use whocalled.us for caller id name lookup.';
$source_param['Get_Caller_ID_Name']['type'] = 'checkbox';
$source_param['Return_to_Who_Called']['desc'] = 'If a valid caller id name is found, provide it back to Who Called for their database.';
$source_param['Return_to_Who_Called']['type'] = 'checkbox';
$source_param['Get_SPAM_Score']['desc'] = 'Use whocalled.us for spam scoring.';
$source_param['Get_SPAM_Score']['type'] = 'checkbox';
$source_param['SPAM_Threshold']['desc'] = 'Specify the listings required to mark a call as spam.';
$source_param['SPAM_Threshold']['type'] = 'number';
$source_param['SPAM_Threshold']['default'] = 10;

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching Who Called ... <br>\n";
	}

	if($run_param['Get_SPAM_Score'] == 'on')
	{
		if($debug)
		{
			print "Testing if spam ... ";
		}
		$url = "http://whocalled.us/do?action=getScore&name=".$run_param['Username']."&pass=".$run_param['Password']."&phoneNumber=$thenumber";
		$value = get_url_contents($url);
		$st_success = strstr($value, "success");
		$st_score = strstr($value, "score");
		$success = substr($st_success,8,1);
		$score = substr($st_score,6);
		if($success=='1')
		{
		  if($score > $run_param['SPAM_Threshold'])
		  {
				$spam = true;
				if($debug)
				{
					print " determined to be SPAM (score: ".$score.")<br>\n";
				}
		  }
		  else if($debug)
			{
				print "Not a SPAM caller (score: ".$score.")<br>\n";
			}
		}
		else if($debug)
		{
			print "Error in Lookup.<br>\n";
		}
	}
	
	if($run_param['Get_Caller_ID_Name'] == 'on')
	{
		if($debug)
		{
			print "Looking up name ... ";
		}
		$url = "http://whocalled.us/do?action=getWho&name=".$run_param['Username']."&pass=".$run_param['Password']."&phoneNumber=$thenumber";
		$value = get_url_contents($url);
		$st_success = strstr($value, "success");
		$st_cid = strstr($value, "who");
		$success = substr($st_success,8,1);
		$cid = substr($st_cid,4);
		if($success=='1')
		{
		  if($cid != '')
		  {
				$caller_id = $cid;
		  }
		  else if($debug)
			{
				print "not found<br>\n";
			}
		}
		else if($debug)
		{
			print "Error in Lookup.<br>\n";
		}
	}
}

if($usage_mode == 'post processing')
{
	//return the value back to Who Called if the user has enabled it and the result didn't come from cache. This will truncate the string to 15 characters
	if(!$cache_found && ($winning_source != 'Who_Called') && ($first_caller_id != '') && ($run_param['Return_to_Who_Called'] == 'on'))
	{
		if($debug)
		{
			print "Reporting value back to Who Called ... ";
		}
		$url = "http://whocalled.us/do?action=report&name=".$run_param['Username']."&pass=".$run_param['Password']."&phoneNumber=$thenumber&date=".date('Y-m-d')."&callerID=".urlencode(substr($first_caller_id,0,15));
		$value = get_url_contents($url);
		if($debug)
		{
			$st_success = strstr($value, "success");
			$st_error = strstr($value, "errorMsg");
			$success = substr($st_success,8,1);
			$error = substr($st_error,9);
			if($success=='1')
			{
				print "Success.<br>\n<br>\n";
			}
			else
			{
				print "Failed with error message: ".$error.".<br>\n<br>\n";
			}
		}
	}
}
?> 