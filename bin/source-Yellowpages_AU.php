<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://yellowpages.com.au - 	These listings include business data for AU.  While some lookups will be fast, some may take take longer than the default 3 seconds to complete.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;

	if($debug)
	{
		print "Searching yellowpages.com.au ... ";
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
//  this section is catching valid numbers of the form 1300 xxx xxx, and perhaps other formats as well
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
		// Since yellowpages.com.au's has no reverse lookup, we'll use google 
		// to get the proper yellowpages.com.au page
		$name = "";

		// Pull 30 (more than 10) results to have a better change of harvesting name directly from google results
		$url = "http://www.google.com/search?num=30&hl=en&lr=&safe=off&as_qdr=all&q=%22".$num1."+".$num2."+".$num3."%22+site%3Awww.yellowpages.com.au+-site%3Awww.yellowpages.com.au%2Fsearch&btnG=Search";

		//$url = "http://www.google.com/search?q=%22".$num1."+".$num2."+".$num3."%22+site:www.yellowpages.com.au";
		$value = get_url_contents($url);

$myFile = "/var/www/html/admin/modules/superfecta/bin/superfecta.txt";
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $value);
fclose($fh);

		// First, check if we can just pull the name directly from google without having to pull the slow yellopages.com.au page
//		$pattern = "/<a href=\"http:\/\/www\.yellowpages\.com\.au\/(?!find|search)[^\"]{1,100}\"[^>]{1,100}>([^-<]{1,100}).{1,100}<div class=\"s\">[^:<]{1,200}<em>".$num1.".{0,1} ".$num2." ".$num3."/i";   //working as of Dec 2010
		$pattern ="/<a href=\"\/url\?q=http:\/\/www\.yellowpages\.com\.au\/.+\">(.{2,30})[ ]-[ ].+Phone number .<b>".$num1.".[ ]".$num2."[ ]".$num3."/";    //working as of May 30, 2012


		if(preg_match($pattern, $value, $match)){
			// Found a usable name in googles search results, use it
			$name = trim(strip_tags($match[1]));
		}else{
			// Looks like we'll have to take the slow route
			if($debug){print "Using deep search for yellowpages.com.au ... ";}
			// Check to see if google thinks there is a result on "www.yellowpages.com.au/find" 
			$pattern = "/href=\"(http:\/\/www\.yellowpages\.com\.au\/find[^\"]+)\"/";
			if(preg_match($pattern, $value, $match)){
				// Search yellowpages.com.au/find
				// www.yellowpages.com.au needs a cookie
	               		$temp_cookie_file = tempnam("/tmp", "CURLCOOKIE");
				// Get the cookie set
				$value = get_url_contents("http://www.yellowpages.com.au",false,"http://www.yellowpages.com.au",$temp_cookie_file);
				// Load the resilts google told us about
				$value = get_url_contents($match[1],false,"http://www.yellowpages.com.au",$temp_cookie_file);
				// Delete the temporary cookie
		                @unlink($temp_cookie_file);
	
				// Get a list of all the phone numbers
				$pattern = "/<span class=\"phoneNumber\">ph: *(\(*\d+\)* \d+ \d+)<\/span>/";
				if(preg_match_all($pattern, $value, $match_numbers)){
					// Get a list of all the names
					$pattern = "/<span id=\"listing-name-[^\"]*\">([^<]+)<\/span>/";
					if(preg_match_all($pattern, $value, $match_names)){
						// Flip the phone number array, so we can use the phone number as the key
						$match_numbers[1] = array_flip($match_numbers[1]);
						if(isset($match_numbers[1][$fullnum]) && isset($match_names[1][$match_numbers[1][$fullnum]])){
							// Lookup the related name based off the phone number
							$name = trim(strip_tags($match_names[1][$match_numbers[1][$fullnum]]));
						}elseif($debug){
							print "Found phone numbers and names, but something is wrong with the source page.<br>\n"; 
						}
					}elseif($debug){
						print "Found phone numbers, but something is wrong with the source page.<br>\n";
					}
				}elseif($debug){
					print "Found on google, but something is wrong with the source page.<br>\n";
				}
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
