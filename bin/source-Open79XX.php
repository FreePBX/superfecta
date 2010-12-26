<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Open 79XX XML Directory lookup. http://web.csma.biz/apps/xml_xmldir.php";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Host address of the Open79XX database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Name']['desc'] = 'schema name of the Open79XX database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_User']['desc'] = 'Username used to connect to the Open79XX database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_Password']['desc'] = 'Password used to connect to the Open79XX database';
$source_param['DB_Password']['type'] = 'password';
$source_param['Filter_Length']['desc'] = 'The number of rightmost digits to check for a match';
$source_param['Filter_Length']['type'] = 'number';
$source_param['Filter_Length']['default'] = 9;


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$wresult_caller_name = "";
	if($debug)
	{
		print "Searching Open 79XX XML Directory... ";
	}
	
	$wquery_input = preg_replace("/\D/","",$thenumber); // strip non-digits
	
	if(strlen($wquery_input) == 0) exit; // abandon search if no number is passed
	if (strlen($wquery_input) > $run_param['Filter_Length']) $wquery_input = substr($wquery_input, -$run_param['Filter_Length']); // keep only the filter_length rightmost digits
	$wdb_handle = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password']) or die("Open 79XX XML connection failed" . mysql_error());
	mysql_select_db($run_param['DB_Name']) or die("Open 79XX XML db open error: " . mysql_error());
	mysql_query("SET NAMES 'utf8'") or die("UTF8 set query  failed: " . mysql_error());
	
	$wquery_string = "SELECT * FROM contacts WHERE (RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(office_phone,' ',''),'+',''),'-',''),'(',''),')','')," . $run_param['Filter_Length'] . ") LIKE '" . $wquery_input . "'
							OR RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(home_phone,' ',''),'+',''),'-',''),'(',''),')','')," . $run_param['Filter_Length'] . ") LIKE '" . $wquery_input ."'
							OR RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cell_phone,' ',''),'+',''),'-',''),'(',''),')','')," . $run_param['Filter_Length'] . ") LIKE '" . $wquery_input ."'
							OR RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(custom_number,' ',''),'+',''),'-',''),'(',''),')','')," . $run_param['Filter_Length'] . ") LIKE '" . $wquery_input ."') LIMIT 1";
	
	$wquery_result = mysql_query($wquery_string) or die("Open 79XX contacts query failed" . mysql_error());
	if(mysql_num_rows($wquery_result) > 0)
	{
		$wquery_row = mysql_fetch_array($wquery_result);
		$wresult_caller_name = $wquery_row["fname"] . " " . $wquery_row["lname"];
        if (($wquery_row["company"]) != "")
        {
	   $wresult_caller_name = $wresult_caller_name." (".$wquery_row["company"].")";
	 }


	}
	
	
	if ($wresult_caller_name != "")
	{
		$caller_id = strip_tags($wresult_caller_name);
	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}
?>
