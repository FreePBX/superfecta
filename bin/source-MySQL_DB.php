<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Query a local or remote MySQL database.";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Host address of the database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Name']['desc'] = 'Name of the database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_User']['desc'] = 'Authentication Username to connect to the database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_Password']['desc'] = 'Password used to connect to the database';
$source_param['DB_Password']['type'] = 'password';
// $source_param['SQL_Query']['desc'] = 'SQL Query used to retrieve the CNAM from the DB. Select result as data from table where telephone field like [NUMBER]. "as data" is important, NUMBER will be replaced by the called number';
$source_param['SQL_Query']['desc'] = 'Structrue a valid MySQL query that returns the value of a single field or multiple fields concatenated into a single value with an alias of "data" (all lower case no quotes) using Select...AS.<br>Example1: SELECT name_field AS data WHERE...<br>Example2: SELECT CONCAT (firstname," ",lastname) AS data WHERE ...<br><br>Wherever you need the incoming CID number to appear in your MySQL query, substitute the string "[NUMBER]" (including square brackets, all uppercase).<br>Example: ...WHERE phone_field LIKE "[NUMBER]"...<br><br>To structure a query with a regular expression that will ignore all non-digit characters stored in the phone number field use REGEXP "[CID_REGEXP]" (including square brackets, all uppercase).<br>Example: SELECT name_field WHERE phone_field REGEXP "[CID_REGEXP]"';
$source_param['SQL_Query']['type'] = 'text';
$source_param['Post_Process_SQL_Query']['desc'] = 'description';
$source_param['Post_Process_SQL_Query']['type'] = 'text';

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$wresult_caller_name = "";
	if($debug)
	{
		print "Searching MySQL Database... ";
	}
	
	$wquery_input = preg_replace("/\D/","",$thenumber); // strip non-digits

	//  Build regular expression from $thenumber to avoid non-digit characters
	$wquery = "[^0-9]*";
	for( $x=0; $x < ((strlen($thenumber))-1); $x++ )
   	{
		$wquery .=  substr($thenumber,$x,1)."[^0-9]*" ;
	}
	$wquery = $wquery.(substr($thenumber,-1))."([^0-9]+|$)";

	if (strlen($wquery_input) == 0) exit; // abandon search if no number is passed
	$wdb_handle = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password']) or die("MySQL connection failed" . mysql_error());
	mysql_select_db($run_param['DB_Name']) or die("MySQL db open error: " . mysql_error());
	mysql_query("SET NAMES 'utf8'") or die("UTF8 set query  failed: " . mysql_error());

	$wquery_string =$run_param['SQL_Query'];
	$wquery_string = str_replace('[NUMBER]', $wquery_input, $wquery_string);
	$wquery_string = str_replace('[CID_REGEXP]', $wquery, $wquery_string);

	//print $wquery_string;
	$wquery_result = mysql_query($wquery_string) or die("MySQL query failed" . mysql_error());
	//print $wquery_result;
	if(mysql_num_rows($wquery_result) > 0)
	{
		$wquery_row = mysql_fetch_array($wquery_result);
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

if($usage_mode == 'post processing')
{
	mysql_select_db($run_param['DB_Name']) or die("MySQL db open error: " . mysql_error());
	mysql_query("SET NAMES 'utf8'") or die("UTF8 set query  failed: " . mysql_error());

//	[SPAM], [CNAM] perhaps [SOURCE] for
	$wquery_string =$run_param['Post_Process_SQL_Query'];
	$wquery_string = str_replace('[CNAM]', $first_caller_id, $wquery_string);
	$wquery_string = str_replace('[SOURCE]', $winning_source, $wquery_string);
	$wquery_result = mysql_query($wquery_string) or die("MySQL query failed" . mysql_error());
}

?>
