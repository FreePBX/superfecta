<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//  Last updated May 29, 2012 by lcg

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://truelocal.com.au - 	These listings include business data for AU.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;

	if($debug)
	{
		print "Searching truelocal.com.au ... ";
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
	
	if($number_error)
	{
		if($debug)
		{
			print "Skipping Source - Non AU number: ".$thenumber."<br>\n";
		}
	}
	else
	{
		// Since truelocal.com.au's has no reverse lookup, we'll use google 
		// to get the proper truelocal.com.au page
		$name = "";
		$url = "http://www.google.com/search?num=10&hl=en&lr=&safe=off&as_qdr=all&q=%22".$num1."+".$num2."+".$num3."%22+site%3Awww.truelocal.com.au%2Fbusiness&btnG=Search";
		$value = get_url_contents($url);

		// Check if we can pull the name from google's index results
//		$pattern = "/<a href=\"http:\/\/www\.truelocal\.com\.au\/business\/[^>]{1,100}>([^,-<]{1,100})/i";  //working as of Dec. 2010
//		$pattern = "/www\.truelocal\.com\.au\/business\/(.{3,40})\/.*$num1\+$num2\+$num3/";   //working as of May 28, 2012 for landlines but returns names of all lower case and hypens in place of spaces.
		$pattern = "/www\.truelocal\.com\.au\/business\/.+?\">(.+?),[ ].+?\/.*$num1\+$num2\+$num3/";  //working as of May 30, 2012
		if(preg_match($pattern, $value, $match)){
			// Found a usable name in googles search results, use it
			$name = trim(strip_tags($match[1]));
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