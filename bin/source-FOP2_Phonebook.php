<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Look up First Name, Last Name, Company Name in the FOP2 visual address book MySQL table<br>This data source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Host address of the FOP2 database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Host']['default'] = 'localhost';
$source_param['DB_Name']['desc'] = 'schema name of the FOP2 database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_Name']['default'] = 'fop2';
$source_param['DB_User']['desc'] = 'Username used to connect to the FOP2 database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_User']['default'] = 'root';
$source_param['DB_Password']['desc'] = 'Password used to connect to the FOP2 database';
$source_param['DB_Password']['type'] = 'password';
$source_param['DB_Password']['default'] = 'passw0rd';

$source_param['CNAM_Type']['desc'] = 'Select how CNAM is returned';
$source_param['CNAM_Type']['type'] = 'select';
$source_param['CNAM_Type']['option'][1] = 'Company_name';
$source_param['CNAM_Type']['option'][2] = 'First_name Last_name';
$source_param['CNAM_Type']['option'][3] = 'Last_name Company_name';
$source_param['CNAM_Type']['default'] = 1;

$source_param['Filter_Length']['desc']='The number of rightmost digits to check for a match';
$source_param['Filter_Length']['type']='number';
$source_param['Filter_Length']['default']= 10;

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching FOP2 ... ";
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
		$wdb_handle = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password']) or die("FOP2 connection failed" . mysql_error());
		mysql_select_db($run_param['DB_Name']) or die("FOP2 db open error: " . mysql_error());
		mysql_query("SET NAMES 'utf8'") or die("UTF8 set query  failed: " . mysql_error());

		// search database
		$wquery_string = 'SELECT firstname, lastname, company FROM visual_phonebook WHERE (phone1 REGEXP '.$wquery.') OR (phone2 REGEXP '.$wquery.') ORDER BY id DESC';
		$wquery_result = mysql_query($wquery_string) or die("FOP2 query failed" . mysql_error());
		if (mysql_num_rows($wquery_result)>0)
		{
                	$wquery_row = mysql_fetch_array($wquery_result);
			$caller_id = $wquery_row["firstname"]." ".$wquery_row["lastname"];
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
