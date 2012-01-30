<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://maps.google.co.uk - 	These listings include business data for the UK.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = true;

	if($debug)
	{
		print "Searching maps.google.co.uk ... ";
	}
	
	if($match = preg_match("/^(((44\s?\d{4}|\(?0\d{4}\)?)\s?\d{3}\s?\d{3})|((44\s?\d{3}|\(?0\d{3}\)?)\s?\d{3}\s?\d{4})|((44\s?\d{2}|\(?0\d{2}\)?)\s?\d{4}\s?\d{4}))(\s?\#(\d{4}|\d{3}))?$/", $thenumber))
	{
		if($debug)
		{
			print "Google Maps UK lookup: {$thenumber}<br>\n";
		}
		// By default, the found name is empty
		$name = "";

		// We'll be searching google maps
		$url = "http://maps.google.co.uk/maps?q={$thenumber}";
		$value = get_url_contents($url);

		// Grab the first result from google maps that matches our phone number
                $pattern = "'jsprops=\"label:\'A\'\"><span>(.*?)</span></a>'si";
		preg_match($pattern, $value, $match);
		if(isset($match[1]) && strlen($match[1])){
			$name = trim(strip_tags($match[1]));
		}
		// If we found a match, return it
		if(strlen($name) > 1)
		{
			$caller_id = $name;
		}
		else if($debug)
		{
			print "not found";
		}
	} else {
		if($debug)
		{
			print "Skipping Source - Non UK number: {$thenumber}<br>\n";
		}
	}
}
?>
