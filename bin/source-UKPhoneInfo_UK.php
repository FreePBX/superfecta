<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.ukphoneinfo.com - The UK Telephone Code Locator will return the exchange or service area location. No names are returned by this source.<br>Because the data provided is less specific than other sources, this data source is usualy configured near the bottom of the list of active data sources.";
$source_param = array();

// Only UK supported here
// if you have time to help us, we will welcome your debug for these sources.

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print 'Searching UKPhoneInfo...<br> ';
	}
	
	// Test for UK
	if (strlen($thenumber) > 11)
	{
		if (substr($thenumber,0,2) == '44')
		{	
			$thenumber = '0'.substr($thenumber,2);
		}
		else if (substr($thenumber,0,4) == '0044')
		{
			$thenumber = '0'.substr($thenumber,4);
		} 
		else if (substr($thenumber,0,5) == '01144')
		{
			$thenumber = '0'.substr($thenumber,5);
		}	
	}

	$url = "http://www.ukphoneinfo.com/search.php?Submit=Submit&d=nl";
	
	$url = $url . "&GNG=" . $thenumber;
	$sresult =  get_url_contents($url);
	
	preg_match_all('=<h2[^>]*>(.*)</h2>=siU', $sresult, $sname);
	
	if (count($sname[1]) > 0)
	{
		$sname = $sname[1][0];
		//$sname = str_replace(chr(160), ' ', $sname[1][0]);
	}
	else
	{
		$sname = "";
	}
	
	if ($sname != "")
	{
		$caller_id = strip_tags($sname);			
	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}
?>