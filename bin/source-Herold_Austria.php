<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//Last edited December 12,2012 by lgaetz

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.herold.at/ - These listings include data for Austriaa.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$value = "";
	$number_error = false;

	if($debug)
	{
		print "Searching http://www.herold.at/ ... ";
	}

	//all number checking removed, Austria numbering conventions are not straightforward,

	// Set the url we're searching for 
//	$url="http://www.herold.at/en/gelbe-seiten/telefon_".$thenumber."/";  //valid December 7, 2011
	$url="http://www.herold.at/telefonbuch/telefon_".$thenumber."/";    //working December 8, 2012

	$value = get_url_contents($url);

	// Patterns to search for
	$regexp = array(
//		"/<span title=\"Siehe Karte Position A\">A<\/span><\/div><\/td><td><h2><a href=.*\/\">(.*)<\/a><\/h2>/",
//		"/<span title=\"Siehe Karte Position A\">A.*\">(.*)<\/a><\/h2>/",
		"/<div class=\"result\"><p class=\"poiIcon none\"><\/p><div class=\"result-wrap\"><h2 class=\"fullw\"><a href=\".*?\">(.*?)<\/a><\/h2><div class=\"addr fullw\">/",
 	);

	// By default, there is no match
	$name = "";

	// Look through each pattern to see if we find a match -- take the first match
	foreach ($regexp as $pattern)
        {
		preg_match($pattern, $value, $match);
		if(isset($match[1]) && (strlen(trim(strip_tags($match[1])))))
                {
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
