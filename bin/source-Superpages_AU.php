<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
// Last edit June 2, 2012

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://superpages.com.au - 	These listings include business data for AU.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;

	if($debug)
	{
		print "Searching superpages.com.au ... ";
	}
	
	// Validate number
	if($match = match_pattern("0[2356789]XXXXXXXX",$thenumber)){
		// Land line
		$num1 = substr($thenumber,0,2);
		$num2 = substr($thenumber,2,4);
		$num3 = substr($thenumber,6,4);
		$fullnum = ".".$num1.". ".$num2."-".$num3;

	}elseif($match = match_pattern("04XXXXXXXX",$thenumber)){
		// Mobile number
		$num1 = substr($thenumber,0,4);
		$num2 = substr($thenumber,4,3);
		$num3 = substr($thenumber,7,3);
		$fullnum = $num1." ".$num2."-".$num3;
		
	}elseif($match = match_pattern("1300XXXXXX",$thenumber)){
		// 1300 XXX XXX number
		$num1 = substr($thenumber,0,4);
		$num2 = substr($thenumber,4,3);
		$num3 = substr($thenumber,7,3);
		$fullnum = $num1." ".$num2." ".$num3;
		
	}elseif($match = match_pattern("13XXXX",$thenumber)){
		// 13 XXXX number
		$num1 = substr($thenumber,0,2);
		$num2 = substr($thenumber,2,4);
		$num3 = "";
		$fullnum = $num1." ".$num2;
		
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
		// Since superpages.com.au's has no reverse lookup, we'll use google to search superpages

		$name = "";
		$url = "http://www.google.com/search?q=%22".$num1."+".$num2."+".$num3."%22+site:www.superpages.com.au";   //working Jun 2, 2012
		$value = get_url_contents($url);

		// check google google resuls 
//		$pattern = "/href=\"(http:\/\/www\.superpages\.com\.au\/page[^\"]+)\"/";   //working Dec 2010
		$pattern = "/<a href=\"\/url\?q=http:\/\/www\.superpages\.com\.au\/p.+?\">(.*?) [-<].*?$num1.{0,20}$num2.{0,10}$num3<\/b>/";   //Working Jun 2, 2012

		preg_match($pattern, $value, $match);
		if(isset($match[1])){
				$name = urldecode(trim(strip_tags($match[1])));
		}else{
/****  Removed on Jun 2, 2012 doesn't seem to be neceesary ******
		// Try another possible match
			// If no "www.superpages.com/au/page" result, check for "www.superpages.com/au/business"
			$pattern = "/href=\"(http:\/\/www\.superpages\.com\.au\/business[^\"]+)\"/";
			preg_match($pattern, $value, $match);
			if(isset($match[1])){
				// Search superpages.com.au/business
				$value = get_url_contents($match[1]);
				$pattern = "/style=\"font-size:13px\">([^<]+)<\/a><\/b>[^<]*<br\/>[^<]*<div class=\"icon_container\">[^<]*<\/div>[^<]*<div class=\"listing_content\">[^<]*<span class=\"phone\">".$fullnum."<\/span>/";
				preg_match($pattern, $value, $match);
				if(isset($match[1])){
					$name = trim(strip_tags($match[1]));
				}
			}		
****  Removed on Jun 2, 2012 doesn't seem to be neceesary ******/
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