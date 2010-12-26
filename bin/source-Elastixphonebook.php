<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Query the Elastix sqlite phone book database.";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Location of the sqlite3 database. (Elastix default: /var/www/db/address_book.db)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Host']['default'] = '/var/www/db/address_book.db';
$source_param['DB_Name']['desc'] = 'schema name of the database. (Elastix default: contact)';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_Name']['default'] = 'contact';



//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$wresult_caller_name = "";
	if($debug)
	{
		print "Searching Elastix address book database... ";
	}
	
	$wquery_input = preg_replace("/\D/","",$thenumber); // strip non-digits
	
	if (strlen($wquery_input) == 0) exit; // abandon search if no number is passed
	
	if($debug)
	{
		print "number: ".$wquery_input."<br>\n";
	}

	
	
	$wquery_string ="SELECT * FROM ".$run_param['DB_Name'] ." WHERE telefono LIKE '".$wquery_input."' LIMIT 1";
	exec("sqlite3 -separator '$-$' -nullvalue 'no-mail' ".$run_param['DB_Host']." \"".$wquery_string.";\"",$E_address_book,$resulterror);
	list($idx,$name,$last_name,$phone_number,$extension,$mail)=explode("$-$",$E_address_book[0]);
	$wresult_caller_name = $name." ". $last_name;

	
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
