<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber 
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.reverseaustralia.com - 	These listings include residential data for AU.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

$source_param['API_Key']['desc'] = 'Your API key from http://www.reverseaustralia.com/developer/';
$source_param['API_Key']['type'] = 'text';
$source_param['API_Key']['default'] = '';
$source_param['Spam_Threshold']['desc'] = 'How sensitive of a spam score to use 1-100, 0 to disable';
$source_param['Spam_Threshold']['type'] = 'number';
$source_param['Spam_Threshold']['default'] = '40';


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;

	if($debug)
	{
		print "Searching reverseaustralia.com ... ";
	}
	
	// Validate number
	if($match = match_pattern("0[2356789]XXXXXXXX",$thenumber)){
		// Land line
		$num1 = substr($thenumber,0,2);
		$num2 = substr($thenumber,2,4);
		$num3 = substr($thenumber,6,4);
		$fullnum = "(".$num1.") ".$num2." ".$num3;


	}elseif($match = match_pattern("04XXXXXXXX",$thenumber)){
		// Mobile number
		$num1 = substr($thenumber,0,4);
		$num2 = substr($thenumber,4,3);
		$num3 = substr($thenumber,7,3);
		$fullnum = $num1." ".$num2." ".$num3;
	}else{
		$number_error = true;
	}
	
	$run_param['API_Key'] = preg_replace('/[^a-z0-9]/', '', strtolower($run_param['API_Key']));
	if (strlen($run_param['API_Key']) != 32)
		$run_param['API_Key'] = false;

	if (!$run_param['API_Key'])
	{
		if ($debug)
			print "Skipping Source - No API key<br>\n";
	}
	else if($number_error)
	{
		if($debug)
			print "Skipping Source - Non AU number: ".$thenumber."<br>\n";
	}
	else
	{
		// Search reverseaustralia.com
		$url = "http://api.reverseaustralia.com/cidlookup.php?format=text&key={$run_param['API_Key']}&q=$thenumber".($run_param['Spam_Threshold']?"&spamthreshold={$run_param['Spam_Threshold']}":'');
		$value = trim(get_url_contents($url));
		// No name, unless we find one
		$name = "";
		if ($value != 'Not found')
			$name = $value;

		// If we found a match, return it
		if(strlen($name) > 1)
		{
			$caller_id = $name;
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
}
?>
