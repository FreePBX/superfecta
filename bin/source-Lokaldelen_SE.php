<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.lokaldelen.se - This listings include data from the Swedish lokaldelen.se directory.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching Lokaldelen.se ... ";
	}
	$number_error = false;
	
	//check for the correct digits for Sweden in international format.

	
	// international dialing prefix + country code + number
	if (strlen($thenumber) > 8)
	{
		if (substr($thenumber,0,2) == '46')
		{
			$thenumber = '0'.substr($thenumber, 2);
		}
		else if (substr($thenumber,0,4) == '0046')
		{
			$thenumber = '0'.substr($thenumber, 4);
		}
		else if (substr($thenumber,0,5) == '01146')
		{
			$thenumber = '0'.substr($thenumber,5);
		}			
		else
		{
			$number_error = true;
		}
	}	
	// number
       if(strlen($thenumber) < 11)
	{
		if (substr($thenumber,0,1) == '0')
		{
			$number_error = false;
		}
		else
		{
			$number_error = true;
		}
	}
	
	if(!$number_error)
	{
		$url="http://www.lokaldelen.se/navigator/-/$thenumber/1/";
		$value = get_url_contents($url);	

		$notfound = strpos($value, "searchResultsTbl"); //No information found
		$notfound = ($notfound < 1); 
		if($notfound)
		{
			$value = "";
		}	
		else
		{
			$start = strpos($value, "fn openPreview");
			$value = substr($value,$start+24);
			$start = strpos($value, ">");
			$value = substr($value,$start+1);
			$end = strpos($value, "</a>");
			$value = substr($value,0,$end);
		}		

		if (strlen($value) > 1)
		{
			$caller_id = strip_tags($value);
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
	else if($debug)
	{
		print "Skipping Source - Non Swedish number: ".$thenumber."<br>\n";
	}

}
?>