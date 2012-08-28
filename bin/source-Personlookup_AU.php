<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//last update: May 28, 2012

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://personlookup.com.au - 	These listings include residential data for AU.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;

	if($debug)
	{
		print "Searching personlookup.com.au ... ";
	}
	
	// Validate number - breaking the $thenumber into $num1, $num2 and $numb3 is no longer requiried for this site
	// but keeping the code here in case it needed in the future
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
	
	if($number_error)
	{
		if($debug)
		{
			print "Skipping Source - Non AU number: ".$thenumber."<br>\n";
		}
	}
	else
	{
		// Search personlookup.com.au
//		$url = "http://personlookup.com.au/browse.aspx?type=search&supplied=number&value=".urlencode($fullnum)."&state=all";  	// good as of Dec 2010
		$url = "http://personlookup.com.au/people?utf8=%E2%9C%93&name=&page=1&number=".$thenumber."&state=";				// good as of May 2012
		$value = get_url_contents($url);

		$name = "";

		//  Define the regex patten to search for
//		$pattern = "/<div class=\"col\">(.+)<\/div>[^<]+<div class=\"col\">[^<]+<\/div>[^<]+<div class=\"col\">.*".$num1.".* ".$num2." ".$num3."<\/div>/";   //good as of Dec 2010
		$pattern = "/<div class=\"col\">(.+)<\/div>/";    //working May 28, 2012  picks up several matches, ignores the first 3 and uses 4th

		preg_match_all($pattern, $value, $match);
		if(isset($match[0][3])){
			$name = trim(strip_tags($match[0][3]));
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
