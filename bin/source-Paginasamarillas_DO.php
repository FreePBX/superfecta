<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//version: November 7, 2011

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.paginasamarillas.com.do - 	Business listings for the Dominican Republic.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$value = "";
	$number_error = false;
	$validnpaCAN = false;

	if($debug)
	{
		print "Searching http://www.paginasamarillas.com.do ... ";
	}
	
	//check for the correct 10 digit phone number.
	if (strlen($thenumber) != 10)
	{
   		$number_error = true;

	}


	if($number_error)
	{
		if($debug)
		{
			print "Skipping - Source requires 10 digit number: ".$thenumber."<br>\n";
		}
	}
	else
	{
		// Set the url we're searching for
		$url="http://www.paginasamarillas.com.do/Searchpg.aspx?SearchType=neg&telefono=$thenumber&searchAction=adv&ciudad=ALL";
		$value = get_url_contents($url);

		// Patterns to search for
		$regexp = array
                (

			"/<div class=\"anunciantesover2\" id=\"div.*\" title=\"(.+)\">/",
                        "/\" title=\"url:(.+)\" rel=\"nofollow/",

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
