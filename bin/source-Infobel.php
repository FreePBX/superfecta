<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.infobel.com - This source includes business and residential data for Belgium, France, Luxembourg, Denmark, Austria, Italy, and Germany";
$source_param = array();
$source_param['Default_Country']['desc'] = 'Default Country to search if the number is not presented in the international format. Enter BE for Belgium, FR for France, LU for Luxembourg, DK for Denmark, AU for Austria, IT for Italy, DE for Germany.';
$source_param['Default_Country']['type'] = 'text';
$source_param['Default_Country']['default'] = 'FR';

// AU, IT and DEsources are a work in progress. They may not work properly now.
// ES source does not work!
// if you have time to help us, we will welcome your debug for these sources.

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print 'Searching Infobel...<br> ';
	}
	$Country = 'None';
	//BE: 8 to 9 digits; FR: 9 digits, IT: 8 to 11 digits, AU: 4 to 13 digits, DK: 8 digits, LU: 6 to 9 digits, ES: 9 digits, DE:6 to 12. 

	$loc_number = $thenumber;
	$adddecode= false;

	// Test for FR
	if (strlen($loc_number) > 10)
	{
		if (substr($loc_number,0,2) == '33')
		{	
			$loc_number = '0'.substr($loc_number,2);
			$Country = 'FR';
		}
		else if (substr($loc_number,0,4) == '0033')
		{
			$loc_number = '0'.substr($loc_number,4);
			$Country = 'FR';
		} 
		else if (substr($loc_number,0,5) == '01133')
		{
			$loc_number = '0'.substr($loc_number,5);
			$Country = 'FR';
		}	
	}

	// Test for ES
	if ($Country == "None" &&(strlen($loc_number)>10))
	{
		if (substr($loc_number,0,2)==34)
		{
			$loc_number = substr($loc_number,2);
			$Country = "ES";
		}
		else if (substr($loc_number,0,4)=='0034')
		{
			$loc_number = substr($loc_number,4);
			$Country = "ES";
		} 
		else if (substr($loc_number,0,5)=='01134')
		{
			$loc_number = substr($loc_number,5);
			$Country = "ES";
		} 
	}	

	// Test for BE
	if ($Country == "None" &&(strlen($loc_number)>9))
	{
		if (substr($loc_number,0,2)==32)
		{
			$loc_number = '0'.substr($loc_number,2);
			$Country = "BE";
		}
		else if (substr($loc_number,0,4)=='0032')
		{
			$loc_number = '0'.substr($loc_number,4);
			$Country = "BE";
		} 
		else if (substr($loc_number,0,5)=='01132')
		{
			$loc_number = '0'.substr($loc_number,5);
			$Country = "BE";
		} 
	}	

	// Test for IT
	if ($Country == "None" &&(strlen($loc_number)>9))
	{
		if (substr($loc_number,0,2)==39)
		{
			$loc_number = substr($loc_number,2);
			$Country = "IT";
		}
		else if (substr($loc_number,0,4)=='0039')
		{
			$loc_number = substr($loc_number,4);
			$Country = "IT";
		} 
		else if (substr($loc_number,0,5)=='01139')
		{
			$loc_number = substr($loc_number,5);
			$Country = "IT";
		} 
	}

	// Test for DK
	if ($Country == "None" &&(strlen($loc_number)>9))
	{
		if (substr($loc_number,0,2)==45)
		{
			$loc_number = substr($loc_number,2);
			$Country = "DK";
		}
		else if (substr($loc_number,0,4)=='0045')
		{
			$loc_number = substr($loc_number,4);
			$Country = "DK";
		} 
		else if (substr($loc_number,0,5)=='01145')
		{
			$loc_number = substr($loc_number,5);
			$Country = "DK";
		} 
	}
	// Test for DE
	if ($Country == "None" &&(strlen($loc_number)>8))
	{
		if (substr($loc_number,0,2)==49)
		{
			$loc_number = '0'.substr($loc_number,2);
			$Country = "DE";
		}
		else if (substr($loc_number,0,4)=='0049')
		{
			$loc_number = '0'.substr($loc_number,4);
			$Country = "DE";
		} 
		else if (substr($loc_number,0,5)=='01149')
		{
			$loc_number = '0'.substr($loc_number,5);
			$Country = "DE";
		} 
	}	

	// Test for LU
	if ($Country == "None" &&(strlen($loc_number)>8))
	{
		if (substr($loc_number,0,3)==352)
		{
			$loc_number = substr($loc_number,3);
			$Country = "LU";
		}
		else if (substr($loc_number,0,5)=='00352')
		{
			$loc_number = substr($loc_number,5);
			$Country = "LU";
		} 
		else if (substr($loc_number,0,6)=='011352')
		{
			$loc_number = substr($loc_number,6);
			$Country = "LU";
		} 
	}

	// Test for AU
	if ($Country == "None" &&(strlen($loc_number)>5))
	{
		if (substr($loc_number,0,2)==43)
		{
			$loc_number = '0'.substr($loc_number,2);
			$Country = "AU";
		}
		else if (substr($loc_number,0,4)=='0043')
		{
			$loc_number = '0'.substr($loc_number,4);
			$Country = "AU";
		} 
		else if (substr($loc_number,0,5)=='01143')
		{
			$loc_number = '0'.substr($loc_number,5);
			$Country = "AU";
		} 
	}	


	
	if ($Country == "None" && (strlen($loc_number) <= 12))
	{
		$Country = $run_param['Default_Country'];
	}
	
	if($debug)
	{
		print $Country . ' : ';
	}
	
	switch ($Country)
	{
		case "BE" :
			$url = "http://www.infobel.com/en/belgium/Inverse.aspx?q=Belgie";
			break;
		case "FR" :
			$url = "http://www.infobel.com/en/france/Inverse.aspx?q=France";
			break;
		case "LU" :
			$url = "http://www.infobel.com/en/luxembourg/Inverse.aspx?q=Luxembourg";
			break;
		case "DK" :
			$url = "http://www.infobel.com/en/denmark/Inverse.aspx?q=Denmark";
			break;
		case "AU" :
			$url = "http://www.infobel.com/en/austria/Inverse.aspx?q=Oostenrijk";
			break;
		case "IT" :
			$url = "http://www.infobel.com/en/italy/Inverse.aspx?q=Italy";
			break;
		case "DE" :
			$url = "http://www.infobel.com/en/germany/Inverse.aspx?q=germany";
			break;
		case "ES" :
			$url = "http://www.infobel.com/en/spain/Inverse.aspx?q=spain";
			break;

		default :
			$url = "";
			break;
	}
	
	if ($url != "")
	{

		$url = $url . "&qPhone=" . $loc_number. "&qSelLang3=&SubmitREV=Zoek&inphCoordType=EPSG";
		$sresult =  get_url_contents($url);
		preg_match_all('/businessdetails.aspx">(.*)<\/a>/U', $sresult, $sname);
		
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
			$caller_id = strip_tags($sname);			
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
	else if($debug)
	{
		print "country source not found<br>\n";
	}
}
?>