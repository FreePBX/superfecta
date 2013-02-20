<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//Last update February 20, 2013 by lgaetz

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.paginebianche.it - These listings include data from the Italian PagineBianche.";
$source_param = array();

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print 'Searching PagineBianche...<br> ';
	}

	// Test number for Italy format
	if (strlen($thenumber) > 10)
	{
		if (substr($thenumber,0,2) == '39')
		{	
			$thenumber = substr($thenumber,2);
		}
		else if (substr($thenumber,0,4) == '0039')
		{
			$thenumber = substr($thenumber,4);
		} 
		else if (substr($thenumber,0,5) == '01139')
		{
			$thenumber = substr($thenumber,5);
		}
	}

	// URL working as of Feb 20, 2013
	$url = "http://www.paginebianche.it/ricerca-da-numero?qs=". $thenumber;
	$sresult =  get_url_contents($url);

	// REGEX working as of Feb 20, 2013 for business numbers, it looks like reverse searching residential numbers is blocked
	preg_match_all('=<h2.class\=\"rgs\"[^>]*>(.*)</h2>=siU', $sresult, $sname);

	if (count($sname[1]) > 0)
	{
		$sname = $sname[1][0];
	}
	else
	{
		$sname = "";
	}
	
	if ($sname != "")
	{
		$caller_id = strip_tags(trim($sname));			
	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}