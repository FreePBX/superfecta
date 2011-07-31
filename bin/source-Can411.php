<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//last edited July 16,2011 by lgaetz


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.411.ca - These listings include business and residential data for Canada.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$value = "";
	$number_error = false;
	$validnpaCAN = false;

	if($debug)
	{
		print "Searching 411.ca ... ";
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
	// number
	if(strlen($thenumber) < 10)
	{
		$number_error = true;

	}

	if(!$number_error)
	{
		$thenumber = (substr($thenumber,0,1) == 1) ? substr($thenumber,1) : $thenumber;
		$npa = substr($thenumber,0,3);

		// Check for valid CAN NPA
		$npalistCAN = array(
			"204", "226", "249", "250", "289", "306", "343", "365", "403", "416", "418", "438",
			"450", "506", "514", "519", "579", "581", "587", "604", "613", "647",
			"705", "709", "778", "780", "807", "819", "867", "873", "902", "905",
                        "800", "866", "877", "888"

			);

		if(in_array($npa, $npalistCAN))
		{
			$validnpaCAN = true;
		}
	}

	if(!$validnpaCAN)
	{
		$number_error = true;
	}

	if($number_error)
	{
		if($debug)
		{
			print "Skipping Source - Non Canadian number: ".$thenumber."<br>\n";
		}
	}
	else
	{
		// Set the url we're searching for valid as of July 16, 2011
		$url="http://www.411.ca/reverse/search/?render=mpAjax&st=reverse&q=$thenumber";
		$value = get_url_contents($url);

		// Patterns to search for
		$regexp = array(
			"/<div class=\"business_name\"><a href=\".*\">(.*)<\/a>/",

		);

 		// By default, there is no match
		$name = "";

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
			$caller_id = $name;
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
}