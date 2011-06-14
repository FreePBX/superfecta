<?php
// this file is designed to be used as an include that is part of a loop.
// If a valid match is found, it should give $caller_id a value
// available variables for use are: $thenumber

// CID Superfecata lookup of MySQL The Address Book http://www.corvalis.net/address/


//  Collect info for The Address Book installation
	$source_desc = "Searches The Address Book http://www.corvalis.net/address/ for appearances of the number and returns the last name, and first name if present, from the contact table.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";
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
	$source_param['tab_address']['desc'] = "Name of the address table, probably 'address' with or without a prefix";
	$source_param['tab_address']['type'] = 'text';
	$source_param['tab_address']['default'] = "address";
	$source_param['tab_contact']['desc'] = "Name of the contact table, probably 'contact' with or without a prefix";
	$source_param['tab_contact']['type'] = 'text';
	$source_param['tab_contact']['default'] = "contact";
	$source_param['tab_otherphone']['desc'] = "Name of the otherphone table, probably 'otherphone' with or without a prefix";
	$source_param['tab_otherphone']['type'] = 'text';
	$source_param['tab_otherphone']['default'] = "otherphone";
	$source_param['Ignore_Keywords']['desc'] = 'If the otherphone table CNAM includes any of the keywords listed here, the otherphone CNAM value will be ignored and the first name last name will be used from the contact table.<br>Seperate keywords with commas.';
	$source_param['Ignore_Keywords']['type'] = 'textarea';
	$source_param['Ignore_Keywords']['default'] = 'fax, cell, mobile';


//  Field names in the address book tables, these should not change from one TAB install to the next
	$tab_id = "id";			// name of id field
	$tab_phone1 = "phone1";   	// name of phone1 field in address table
	$tab_phone2 = "phone2";   	// name of phone2 field in address table
	$tab_othernum = "phone";	// name of phone field in otherphone table
	$tab_type = "type";		// name of type field in otherphone table
	$tab_ln = "lastname";		// name of lastname field in contact table
	$tab_fn = "firstname";		// name of firstname field in contact table


//  run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{

	// initialize variables
        $wquery = "";
	$wresult_caller_name = "";
        $wquery_string = "";
        $wquery_result = "";
        $wquery_row = "";

	//  Check that there are enough digits to match
	If (strlen($thenumber) < $run_param['tab_digits'])
	{
		if($debug)
		{
			print "<br/>Not enough digits, exiting";
		}
		exit;
	}

	//  trim incoming number to specified filter length
	$thenumber = substr($thenumber, (-1*$run_param['tab_digits']));

	// Process ignore words
        $key_words = array();
	$temp_array = explode(',',(isset($run_param['Ignore_Keywords'])?$run_param['Ignore_Keywords']:$source_param['Ignore_Keywords']['default']));
	foreach($temp_array as $val)
	{
		$key_words[] = trim($val);
	}

	if($debug)
	{
		print "<br/>Searching The Address Book for: ".$thenumber." ...";
	}

	//  Build regular expression from the modified $thenumber to avoid non-digit characters stored in database
	$wquery = "'[^0-9]*";
	for( $x=0; $x < ((strlen($thenumber))-1); $x++ )
   	{
		$wquery .=  substr($thenumber,$x,1)."[^0-9]*" ;
	}
	$wquery = $wquery.(substr($thenumber,-1))."([^0-9]+|$)'";

	//  Make a MySQL Connection
	mysql_connect($run_param['tab_server'], $run_param['tab_user'], $run_param['tab_password']) or die(mysql_error());
	mysql_select_db($run_param['tab_dbase']) or die(mysql_error());

	//  Search phone1 and phone2 fields in theaddressbook
	$wquery_string = "SELECT * FROM ".$run_param['tab_contact']." INNER JOIN ".$run_param['tab_address']." ON ".$run_param['tab_address'].".id = ".$run_param['tab_contact'].".id WHERE (".$tab_phone1." REGEXP ".$wquery.") OR (".$tab_phone2." REGEXP ".$wquery.") ORDER BY lastupdate DESC";
	$wquery_result = mysql_query($wquery_string);

	if (mysql_num_rows($wquery_result)>0)
        {
       	        // If result is found in phone1 or phone2
		$wquery_row = mysql_fetch_array($wquery_result);
		$wresult_caller_name = $wquery_row[$tab_fn]." ".$wquery_row[$tab_ln];
	}
	else
	{
		//  If no result in phone1 or phone2 search phone field in otherphone table
		$wquery_string = "SELECT * FROM ".$run_param['tab_contact']." INNER JOIN ".$run_param['tab_otherphone']." ON ".$run_param['tab_otherphone'].".id = ".$run_param['tab_contact'].".id WHERE ".$tab_othernum." REGEXP ".$wquery." ORDER BY lastupdate DESC";
		$wquery_result = mysql_query($wquery_string);
	        $wquery_row = mysql_fetch_array($wquery_result);
		if (mysql_num_rows($wquery_result)>0)
	        {
			$wresult_caller_name = $wquery_row[$tab_type];
			//  Check to see if returned name is on the ignore words list, if so  return firstname lastname instead
			$test_string = str_ireplace($key_words,'',$wresult_caller_name);
			if($test_string == "")
			{
				$wresult_caller_name = $wquery_row[$tab_fn]." ".$wquery_row[$tab_ln];
				if($debug)
				{
					print "<br/>found word on ignore list, substituting contact names ";
				}
			}
                }
        }

	//  Pass result to Superfecta module via $caller_id variable
	if ($wresult_caller_name != "")
	{
		$caller_id = trim(strip_tags($wresult_caller_name));
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
