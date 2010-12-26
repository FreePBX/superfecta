<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.vemringde.se - Datasource from the Swedish vemringde.se directory devoted to identifying telemarketers. These listings are provided by other users of this service.";
$source_param = array();
$source_param['SPAM_Threshold']['desc'] = 'Specify the # of listings required to mark a call as spam.';
$source_param['SPAM_Threshold']['type'] = 'number'; 
$source_param['SPAM_Threshold']['default'] = 5;
$source_param['API_Key']['desc'] = 'API Key is REQUIRED. Available from www.vemringde.se (It is Free).';
$source_param['API_Key']['type'] = 'text';
//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching Vemringde.se ... ";
	}
	$number_error = false;
	
	//check for the correct digits for Sweden in international format.

	
	// international dialing prefix + country code + number
	if (strlen($thenumber) > 8)
	{
		if (substr($thenumber,0,2) == '46')
		{
			$thenumber = '0'.substr($thenumber, 2);
		}
		else if (substr($thenumber,0,4) == '0046')
		{
			$thenumber = '0'.substr($thenumber, 4);
		}
		else if (substr($thenumber,0,5) == '01146')
		{
			$thenumber = '0'.substr($thenumber,5);
		}			
		else
		{
			$number_error = true;
		}
	}	
	// number
       if(strlen($thenumber) < 11)
	{
		if (substr($thenumber,0,1) == '0')
		{
			$number_error = false;
		}
		else
		{
			$number_error = true;
		}
	}

	//this line is to allow foreign numbers to be checked as this source seems to accept some foreign numbers.
	//however it is to be noted that these numbers will be passed as is, without any international format check or else.
	//you will have to make sure that these foreign numbers are transmitted properly with a 00 in front of the country code.
	//it would have be possible to add the 00 to all non SE numbers, but the database seems to have also some numbers not
	//following the same policy.

	$number_error=false; 

	
	if(!$number_error)
	{
		$url="http://api.vemringde.se/?apikey=".$run_param['API_Key']."&q=$thenumber&e=1&n=".$run_param['SPAM_Threshold']."";
		$value = get_url_contents($url);	
		if($debug)

		if($value == "0") //No information found
		{
			$value = "";
		}	

		if (strlen($value) > 1)
		{
			$spam = true;
			$caller_id = strip_tags($value);
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
	else if($debug)
	{
		print "Skipping Source - Non Swedish number: ".$thenumber."<br>\n";
	}

}
?>