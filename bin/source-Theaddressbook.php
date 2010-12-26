<?php
// this file is designed to be used as an include that is part of a loop.
// If a valid match is found, it should give $caller_id a value
// available variables for use are: $thenumber

// CID Superfecata lookup of The Address Book http://www.corvalis.net/address/
// The MySQL query for ignoring non-digit characters from the phone number fields was 
// taken from the Ateridex lookup source, other parts were taken from the MySQL lookup source.
// The nesting of mysql queries is because 'The address Book' stores information accross
// multiple tables.  Limiting the nested query to one row is necessary to 
// avoid problems if the same phone number appears in the address book twice
// This data source was provided by lgaetz
// Contact 'lgaetz' on PBXinaflash forums for questions/comments


//  Collect info for The Address Book installation
	$source_desc = "Searches The Address Book http://www.corvalis.net/address/ for appearances of the number and returns the last name, and first name if present, from the contact table. Phone numbers can be stored with [space] + - ( and )<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";
	$source_param['tab_server']['desc'] = 'Host address of The Address Book database. (localhost if the database is on the same server as FreePBX.)';
	$source_param['tab_server']['type'] = 'text';
	$source_param['tab_server']['default'] = 'localhost';
	$source_param['tab_dbase']['desc'] = 'Database name of The Address Book database';
	$source_param['tab_dbase']['type'] = 'text';
	$source_param['tab_dbase']['default'] = 'tab';
	$source_param['tab_user']['desc'] = 'Username used to connect to the MySQL database';
	$source_param['tab_user']['type'] = 'text';
	$source_param['tab_user']['default'] = 'root';
	$source_param['tab_password']['desc'] = 'Password used to connect to the MySQL database';
	$source_param['tab_password']['type'] = 'password';
	$source_param['tab_password']['default'] = 'passw0rd';
	$source_param['tab_digits']['desc'] = 'The number of rightmost digits to check for a match.';
	$source_param['tab_digits']['type'] = 'number';
	$source_param['tab_digits']['default'] = 10;
	$source_param['tab_address']['desc'] = "Name of the address table, probably address with or without a prefix";
	$source_param['tab_address']['type'] = 'text';
	$source_param['tab_address']['default'] = "address";
	$source_param['tab_contact']['desc'] = "Name of the contact table, probably contact with or without a prefix";
	$source_param['tab_contact']['type'] = 'text';
	$source_param['tab_contact']['default'] = "contact";
	$source_param['tab_otherphone']['desc'] = "Name of the otherphone table, probably otherphone with or without a prefix";
	$source_param['tab_otherphone']['type'] = 'text';
	$source_param['tab_otherphone']['default'] = "otherphone";


//  Field names in the address book tables, these should not change from one TAB install to the next
	$tab_id = "id";			// name of id field
	$tab_phone1 = "phone1";   		// name of phone1 field in address table
	$tab_phone2 = "phone2";   		// name of phone2 field in address table
	$tab_othernum = "phone";		// name of phone field in otherphone table
	$tab_type = "type";			// name of type field in otherphone table
	$tab_ln = "lastname";		// name of lastname field in contact table
	$tab_fn = "firstname";		// name of firstname field in contact table


//  run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{

	// incoming CID number
	$wquery_input = $thenumber;

	$wresult_caller_name = "";
	if($debug)
	{
		print "<br/>Searching The Address Book ...";
	}

	//  Check that there are enough digits to match 
	If (strlen($wquery_input) < $run_param['tab_digits'])
	{
		if($debug)
		{
			print "<br/>Not enough digits, exiting";
		}
		exit;   
	}

	//  strip off non-signifigant digits
	$wquery_input = substr($wquery_input, (-1*$run_param['tab_digits']));     
	
	if($debug)
	{
		print "<br/>searching for: ".$wquery_input;
	}

	//  Make a MySQL Connection
	mysql_connect($run_param['tab_server'], $run_param['tab_user'], $run_param['tab_password']) or die(mysql_error());
	mysql_select_db($run_param['tab_dbase']) or die(mysql_error());

	//  First search phone1 field in address table
	$wquery_string = "SELECT * FROM ".$run_param['tab_contact']." where ".$tab_id." = (select ".$tab_id." from ".$run_param['tab_address']." where RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(".$tab_phone1.",' ',''),'+',''),'-',''),'(',''),')',''),".$run_param['tab_digits'].") LIKE ".$wquery_input." LIMIT 1)";
	$wquery_result = mysql_query($wquery_string);

	//  If no result, search phone2 field in address table
	if(mysql_num_rows($wquery_result) == 0)
	{
		$wquery_string = "SELECT * FROM ".$run_param['tab_contact']." where ".$tab_id." = (select ".$tab_id." from ".$run_param['tab_address']." where RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(".$tab_phone2.",' ',''),'+',''),'-',''),'(',''),')',''),".$run_param['tab_digits'].") LIKE ".$wquery_input." LIMIT 1)";
		$wquery_result = mysql_query($wquery_string);
	}

	//  If rows are found, check to see if firstname is defined (lastname is mandatory for TAB)
	if(mysql_num_rows($wquery_result) > 0)
	{
		$wquery_row = mysql_fetch_array($wquery_result);
		$wresult_caller_name = $wquery_row[$tab_ln];
		if ($wquery_row[$tab_fn] == "")
		{
			$wresult_caller_name = $wquery_row[$tab_ln];
		}
		else
		{
			$wresult_caller_name = $wquery_row[$tab_fn]." ".$wquery_row[$tab_ln];
		}
	}

	//  If still no result, search phone field in otherphone table
	if(mysql_num_rows($wquery_result) == 0)
	{
		$wquery_string = "SELECT * FROM ".$run_param['tab_otherphone']." where RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(".$tab_othernum.",' ',''),'+',''),'-',''),'(',''),')',''),".$run_param['tab_digits'].") LIKE ".$wquery_input." LIMIT 1";
		$wquery_result = mysql_query($wquery_string);

		//  If a result is found then extract name from MySQL query
		if(mysql_num_rows($wquery_result) > 0)
		{
			$wquery_row = mysql_fetch_array($wquery_result);
			$wresult_caller_name = $wquery_row[$tab_type];
		}
	}

	//  Pass result to Superfecta module via $caller_id variable	
	if ($wresult_caller_name != "")
	{
		$caller_id = strip_tags($wresult_caller_name);
		if($debug)
		{
			print "<br/>found, returning: ";
		}

	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}
?>