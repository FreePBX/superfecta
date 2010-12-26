<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.paginebianche.it - These listings include data from the Italian PagineBianche.";
$source_param = array();

// Only UK supported here
// if you have time to help us, we will welcome your debug for these sources.

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print 'Searching PagineBianche...<br> ';
	}
	
	// Test for Italy
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

	$url = "http://www.paginebianche.it/execute.cgi?ver=default&font=default&btt=1&ts=106&cb=8&l=it&mr=10&rk=&om=";
	
	$url = $url . "&qs=" . $thenumber;
	$sresult =  get_url_contents($url);
	
	preg_match_all('=<h3.class\=\"org\"[^>]*>(.*)</h3>=siU', $sresult, $sname);
	
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