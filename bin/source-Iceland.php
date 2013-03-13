<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//last edited Mar 13, 2013 by lgaetz


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://ja.is - Listings for Iceland.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$value = "";
	$number_error = false;
	$number = "";
	$name = "";

	if($debug)
	{
		print "Searching http://ja.is ... ";
	}

	//check for acceptable number format for Iceland
	if (function_exists(match_pattern))
	{
		if (match_pattern("[4-9]XXXXXX",$thenumber))
		{
			$number = $thenumber;
			if($debug)
			{
				print "<br>Matched pattern [4-9]XXXXXX ... ";
			}
		}
		else if (match_pattern("354[4-9]XXXXXX",$thenumber))
		{
			$number = $thenumber;
			if($debug)
			{
				print "<br>Matched pattern 354[4-9]XXXXXX ... ";
			}

		}
		else if (match_pattern("00354[4-9]XXXXXX",$thenumber))
		{
			$number = $thenumber;
			if($debug)
			{
				print "<br>Matched pattern 00354[4-9]XXXXXX ... ";
			}

		}
		else
		{
			$number_error = true;
			if($debug)
				{
					print "<br>Skipping Source - Non Icelandic number: ".$thenumber."<br>\n";
				}
		}
	}
	else
	{
		$number = $thenumber;
	}

	if(!$number_error)
	{
		// Set the url we're searching for valid as of Mar 13, 2013
		$url="http://ja.is/?q=".$number;
		$value = get_url_contents($url);

		// Patterns to search for
		$regexp = array(
			"/<h3 class=\"cut-off\"><a href=\"\/.*?\/\" class=\"cut\">(.+?)<\/a><\/h3>/",			//working Mar 13, 2013
		);

		

		// Look through each pattern to see if we find a match -- take the first match
		foreach ($regexp as $pattern)
		{
			preg_match($pattern, $value, $match);
			if(isset($match[1]) && (strlen(trim(strip_tags($match[1]))))) {
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