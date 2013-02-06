<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//Last edited Feb 6, 2013 by lgaetz

//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Searches an AsteriDex Database - local or remote.";
$source_param['DB_Host']['desc'] = 'Host address of the Asteridex database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Host']['default'] = 'localhost';
$source_param['DB_Name']['desc'] = 'Database name of the Asteridex database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_Name']['default'] = 'asteridex';
$source_param['DB_User']['desc'] = 'Username used to connect to the Asteridex database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_User']['default'] = 'root';
$source_param['DB_Password']['desc'] = 'Password used to connect to the Asteridex database';
$source_param['DB_Password']['type'] = 'password';
$source_param['DB_Password']['default'] = 'passw0rd';
$source_param['Filter_Length']['desc'] = 'The number of rightmost digits to check for a match. Enter the word false to disable this setting';
$source_param['Filter_Length']['type'] = 'number';
$source_param['Filter_Length']['default'] = 10;
$source_param['Add_inbound_call_to_AsteriDex']['desc'] = 'Enable to add all inbound caller names to Asteridex';
$source_param['Add_inbound_call_to_AsteriDex']['type'] = 'checkbox';
$source_param['Add_inbound_call_to_AsteriDex']['default'] = "off";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching AsteriDex ... ";
	}

	// Initialize variables
	$value = "";
	$wquery_input = $thenumber;

	// check if user wants to use filter length
	if ($run_param['Filter_Length'] != false)  {
		// keep only the filter_length rightmost digits
		if (strlen($wquery_input) > $run_param['Filter_Length']) $wquery_input = substr($wquery_input, -$run_param['Filter_Length']);
	}

	$link = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password'])or die("AsteriDex connection failed:". $run_param['DB_Host']);
	mysql_select_db($run_param['DB_Name']) or die("AsteriDex data base open failed");

	//  Build regular expression from the number to avoid non-digit characters stored in database
	$wquery = "'[^0-9]*";
	for( $x=0; $x < ((strlen($wquery_input))-1); $x++ )
   	{
		$wquery .=  substr($wquery_input,$x,1)."[^0-9]*" ;
	}
	$wquery = $wquery.(substr($wquery_input,-1))."([^0-9]+|$)'";

	// query database
	$query = "SELECT * FROM `user1` where `out` REGEXP ".$wquery;
	$result = mysql_query($query) or die("AsteriDex query failed: $query");

	// Get first name if any results are returned from query
	if (mysql_num_rows($result)>0)
	{
		$row = mysql_fetch_array($result);
		$value = $row["name"];
	}

	mysql_close($link);              // close link to database

	if(strlen($value) > 0)
	{
		$caller_id = trim($value);
	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}

// run post processing only if user enabled, only if the winning source is not AsteriDex, only if CNAM is found
if($usage_mode == 'post processing'  && $run_param['Add_inbound_call_to_AsteriDex'] == "on"  && $winning_source != "AsteriDex" && $first_caller_id)
{
	/**************************
	Available Variables:
	$winning_source is the name of the source that found the CNAM, if AsteriDex then $wining_source == "AsteriDex"
	$thenumber is the Caller ID of the call after the Superfecta rules have modified it
	$thenumberorig is the raw Caller ID of the call before it hits superfecta
	$first_caller_id is the found CNAM
	**********************/

	// Initialize variables
	$value = "";
	$wquery_input = $thenumber;

	// check if user wants to use filter length
	if ($run_param['Filter_Length'] != false)  {
		// keep only the filter_length rightmost digits
		if (strlen($wquery_input) > $run_param['Filter_Length']) $wquery_input = substr($wquery_input, -$run_param['Filter_Length']);
	}

	//  Build regular expression from the number to avoid non-digit characters stored in database
	$wquery = "'[^0-9]*";
	for( $x=0; $x < ((strlen($wquery_input))-1); $x++ )
   	{
		$wquery .=  substr($wquery_input,$x,1)."[^0-9]*" ;
	}
	$wquery = $wquery.(substr($wquery_input,-1))."([^0-9]+|$)'";

	// open link to dbase
	$link = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password'])or die("AsteriDex connection failed:". $run_param['DB_Host']);
	mysql_select_db($run_param['DB_Name']) or die("AsteriDex data base open failed");
	mysql_set_charset ('utf8');

	// check database to see if number exists already
	$query = "SELECT * FROM `user1` where `out` REGEXP ".$wquery;
	$result = mysql_query($query) or die("AsteriDex query failed: $query");

	// only add caller to database if number doesn't already exist
	if (mysql_num_rows($result)>0)
	{
		if($debug)
		{
			print "Number is already in AsteriDex ...<br> ";
		}
	} 
	else
	{
		if($debug)
		{
			print "Adding caller to AsteriDex ...<br> ";
		}

		// This INSERT statement may need work to prevent injection 
		$query = "INSERT INTO `user1` (`id`, `name`, `in`, `out`, `dialcode`) VALUES ('', '".mysql_real_escape_string($first_caller_id)."', '', '".$thenumber."','')";
		$result = mysql_query($query) or die("AsteriDex INSERT failed: $query");
	}

	// close link to dbase
	mysql_close($link);

}