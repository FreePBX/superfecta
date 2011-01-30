<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Query a local or remote MS SQL database.";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Host address of the database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Name']['desc'] = 'schema name of the database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_User']['desc'] = 'Username used to connect to the database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_Password']['desc'] = 'Password used to connect to the database';
$source_param['DB_Password']['type'] = 'password';
$source_param['SQL_Query']['desc'] = 'SQL Query used to retrieve the Dialer Name. select result as data from table where telfield like [NUMBER]. "as data" is important, NUMMBER will be replaced by the called number';
$source_param['SQL_Query']['type'] = 'text';


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$wresult_caller_name = "";
	if($debug)
	{
		print "Searching MS SQL Database... ";
	}
	
	$wquery_input = preg_replace("/\D/","",$thenumber); // strip non-digits
	
	if (strlen($wquery_input) == 0) exit; // abandon search if no number is passed
	$wdb_handle = mssql_connect($run_param['DB_Host'],$run_param['DB_User'],$run_param['DB_Password']) or die("MS SQL connection failed" . mssql_get_last_message());
    mssql_select_db($run_param['DB_Name']) or die("MS SQL db open error: " . mssql_get_last_message());
	//mssql_query("SET NAMES 'utf8'") or die("UTF8 set query  failed: " . mssql_get_last_message());
	
	$wquery_string =$run_param['SQL_Query'];
	$wquery_string = str_replace('[NUMBER]', $wquery_input, $wquery_string);
	//print $wquery_string;
	$wquery_result = mssql_query($wquery_string) or die("MS SQL query failed" . mssql_get_last_message());
	//print $wquery_result;
	if(mssql_num_rows($wquery_result) > 0)
	{
		$wquery_row = mssql_fetch_array($wquery_result);
		$wresult_caller_name = $wquery_row["data"];
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
