<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Look up First Name and Last Name in vTiger CRM DB, local or remote.<br>This data source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Host address of the vTiger CRM database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Host']['default'] = 'localhost';
$source_param['DB_Name']['desc'] = 'schema name of the vTiger CRM database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_Name']['default'] = 'vtigercrm521';
$source_param['DB_User']['desc'] = 'Username used to connect to the  vTiger CRM database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_User']['default'] = 'root';
$source_param['DB_Password']['desc'] = 'Password used to connect to the  vTiger CRM database';
$source_param['DB_Password']['type'] = 'password';
$source_param['DB_Password']['default'] = 'passw0rd';
$source_param['Search_Contacts']['desc'] = 'Include Contact records in search';
$source_param['Search_Contacts']['type'] = 'checkbox';
$source_param['Search_Contacts']['default'] = "on";
$source_param['Search_Accounts']['desc'] = 'Include Account records in search';
$source_param['Search_Accounts']['type'] = 'checkbox';
$source_param['Search_Accounts']['default'] = "on";
$source_param['Search_Leads']['desc'] = 'Include Leads records in search';
$source_param['Search_Leads']['type'] = 'checkbox';
$source_param['Search_Leads']['default'] = "on";
$source_param['Filter_Length']['desc']='The number of rightmost digits to check for a match';
$source_param['Filter_Length']['type']='number';
$source_param['Filter_Length']['default']= 10;

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching vTiger ... ";
	}

	// Initialize variables
	$wquery = "";
	$wquery_string = "";
	$wquery_result = "";

	// Abandon search if not enough digits in $thenumber
	if (strlen($thenumber) < $run_param['Filter_Length'])
	{
		If ($debug)
		{
			Print "Not enough digits";
		}
	}
	else
	{
        	//  trim incoming number to specified filter length
		$thenumber = substr($thenumber, (-1*$run_param['Filter_Length']));

		//  Build regular expression from modified $thenumber to avoid non-digit characters
		$wquery = "'[^0-9]*";
		for( $x=0; $x < ((strlen($thenumber))-1); $x++ )
	   	{
			$wquery .=  substr($thenumber,$x,1)."[^0-9]*" ;
		}
		$wquery = $wquery.(substr($thenumber,-1))."([^0-9]+|$)'";

        	//  Connect to database
		$wdb_handle = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password']) or die("vTiger connection failed" . mysql_error());
		mysql_select_db($run_param['DB_Name']) or die("vTiger db open error: " . mysql_error());
		mysql_query("SET NAMES 'utf8'") or die("UTF8 set query  failed: " . mysql_error());

		if ($run_param['Search_Contacts'] == "on")
                {
			// search contacts in database
	       		$wquery_string = 'SELECT firstname, lastname FROM vtiger_contactdetails INNER JOIN vtiger_contactsubdetails ON vtiger_contactsubdetails.contactsubscriptionid = vtiger_contactdetails.contactid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid WHERE (phone REGEXP '.$wquery.') OR (mobile REGEXP '.$wquery.') OR (fax REGEXP '.$wquery.') OR (homephone REGEXP '.$wquery.') OR (otherphone REGEXP '.$wquery.') ORDER BY modifiedtime DESC';
	                $wquery_result = mysql_query($wquery_string) or die("vTiger contacts query failed" . mysql_error());
                       	if (mysql_num_rows($wquery_result)>0)
			{
                		$wquery_row = mysql_fetch_array($wquery_result);
				$caller_id = $wquery_row["firstname"]." ".$wquery_row["lastname"];
			}
		}
		if (($run_param['Search_Accounts'] == "on") && ($caller_id == ""))
                {
			// search accounts in database
	       		$wquery_string = 'SELECT accountname FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid WHERE (phone REGEXP '.$wquery.') OR (otherphone REGEXP '.$wquery.') OR (fax REGEXP '.$wquery.') ORDER BY modifiedtime DESC';
	                $wquery_result = mysql_query($wquery_string) or die("vTiger accounts query failed" . mysql_error());
                       	if (mysql_num_rows($wquery_result)>0)
			{
                		$wquery_row = mysql_fetch_array($wquery_result);
				$caller_id = $wquery_row["accountname"];
			}
		}
		if (($run_param['Search_Leads'] == "on") && ($caller_id == ""))
                {
			// search leads in database
	       		$wquery_string = 'SELECT firstname, lastname FROM vtiger_leaddetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid WHERE (phone REGEXP '.$wquery.') OR (mobile REGEXP '.$wquery.') OR (fax REGEXP '.$wquery.') ORDER BY modifiedtime DESC';
	                $wquery_result = mysql_query($wquery_string) or die("vTiger Leads query failed" . mysql_error());
                       	if (mysql_num_rows($wquery_result)>0)
			{
                		$wquery_row = mysql_fetch_array($wquery_result);
				$caller_id = $wquery_row["firstname"]." ".$wquery_row["lastname"];
			}
		}

                // Close dbase connection
               	mysql_close($wdb_handle);

		if ($caller_id == "")
                {
			if($debug)
			{
				print "not found<br>\n";
			}
		}
		else
                {
                $caller_id = trim($caller_id);
                }
	}
}
?>
