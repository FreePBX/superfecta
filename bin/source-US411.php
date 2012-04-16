<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//April 16,2021 by Ward Mundy


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://411.info - These listings include business and residential data for USA.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$value = "";
        $name = ""
	$number_error = false;

	if($debug)
	{
		print "Searching 411.info ... ";
	}

	//check for the correct 11 digits NANP phone numbers in international format.
	// country code + number
	if (strlen($thenumber) == 11)
	{
		if (substr($thenumber,0,1) == 1)
		{
			$thenumber = substr($thenumber,1);
		}
		else
		{
			$number_error = true;
		}

	}
	// international dialing prefix + country code + number
	if (strlen($thenumber) > 11)
	{
		if (substr($thenumber,0,3) == '001')
		{
			$thenumber = substr($thenumber, 3);
		}
		else
		{
			if (substr($thenumber,0,4) == '0111')
			{
				$thenumber = substr($thenumber,4);
			}
			else
			{
				$number_error = true;
			}
		}

	}

	if(strlen($thenumber) < 10)
	{
		$number_error = true;
	}

	if($number_error)
	{
		if($debug)
		{
			print "Improper number format: ".$thenumber."<br>\n";
		}
	}
	else
	{
        	$thenumber = (substr($thenumber,0,1) == 1) ? substr($thenumber,1) : $thenumber;
		// Set the url we're searching for valid as of April 16, 2012
		$url="http://411.info/reverse/?r=$thenumber";
		$value = get_url_contents($url);

		// Patterns to search for
		$regexp = array(
			"/<span class=\"list_title\"><a href=\".*\">(.*)<\/a>/",

		);

		// Look through each pattern to see if we find a match -- take the first match
                foreach ($regexp as $pattern){
			preg_match($pattern, $value, $match);
			if(isset($match[1]) && (strlen(trim(strip_tags($match[1]))))){
				$name = trim(strip_tags($match[1]));
				break;
			}
		}

		// If we found a match, return it
		if(strlen($name) > 1)
		{
			$caller_id = trim(strip_tags($name));
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
}
