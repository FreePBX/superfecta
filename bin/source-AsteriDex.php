<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Searches an AsteriDex Database - local or remote.";
$source_param['DB_Host']['desc'] = 'Host address of the Asteridex database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Host']['default'] = $dsn['hostspec'];
$source_param['DB_Name']['desc'] = 'Database name of the Asteridex database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_Name']['default'] = 'asteridex';
$source_param['DB_User']['desc'] = 'Username used to connect to the Asteridex database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_User']['default'] = 'root';
//$source_param['DB_User']['default'] = $dsn[username];
$source_param['DB_Password']['desc'] = 'Password used to connect to the Asteridex database';
$source_param['DB_Password']['type'] = 'password';
$source_param['DB_Password']['default'] = 'passw0rd';
//$source_param['DB_Password']['default'] = $dsn[password];
$source_param['Filter_Length']['desc'] = 'The number of rightmost digits to check for a match';
$source_param['Filter_Length']['type'] = 'number';
$source_param['Filter_Length']['default'] = 10;
//$dbname = "asteridex";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching AsteriDex ... ";
	}

	$value = "";
	$wquery_input = preg_replace("/\D/","",$thenumber); // strip non-digits
	if(strlen($wquery_input) == 0) exit; // abandon search if no number is passed
	if (strlen($wquery_input) > $run_param['Filter_Length']) $wquery_input = substr($wquery_input, -$run_param['Filter_Length']); // keep only the filter_length rightmost digits
//include_once("/var/www/html/asteridex4/config.inc.php");
	$link = mysql_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password'])or die("AsteriDex connection failed:". $run_param['DB_Host']);
//	$link = mysql_connect($dsn[hostspec], "root", "passw0rd")or die("AsteriDex connection failed");
	mysql_select_db($run_param['DB_Name']) or die("AsteriDex data base open failed");
//	mysql_select_db($dbname) or die("AsteriDex data base open failed");
	$query = "SELECT * FROM `user1` where RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`out`,' ',''),'+',''),'-',''),'(',''),')','')," . $run_param['Filter_Length'] . ") LIKE ".$wquery_input;
//	$query = "SELECT * FROM `user1` where RIGHT(`out`,".$run_param['Filter_Length'] . ") LIKE ".$wquery_input;
	$result = mysql_query($query) or die("AsteriDex query failed: $query");
	if (mysql_num_rows($result)>0)
	{
		$row = mysql_fetch_array($result);
		$value = $row["name"];
	}
	mysql_close($link);
	if(strlen($value) > 0)
	{
		$caller_id = $value;
	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}
?>
