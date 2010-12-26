<?php
//by pbxinaflash.com forum's user Nixi.
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//data source provided by nixi

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.118700.se - This listing include data from the Swedish 118700.se directory.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();
$source_param['Search_Type']['desc'] = 'Select which sources you want to search';
$source_param['Search_Type']['type'] = 'select';
$source_param['Search_Type']['option'][0] = 'Residential';
$source_param['Search_Type']['option'][1] = 'Business';
$source_param['Search_Type']['option'][2] = 'Residential & Business';
$source_param['Search_Type']['default'] = 2;


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching www.118700.se... ";
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
		else
		{
			if (substr($thenumber,0,4) == '0046')
			{
				$thenumber = '0'.substr($thenumber, 4);
			}
			else
			{
				if (substr($thenumber,0,5) == '01146')
				{
					$thenumber = '0'.substr($thenumber,5);
				}			
				else
				{
					$number_error = true;
				}
			}
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
		if($run_param['Search_Type'] != 1)
		{	
	
			if($debug)
			{
				print "Searching 118700.se residential ... ";
			}
		
			$url="http://mobil.118700.se/personsearch.aspx?q=$thenumber";
			$value = get_url_contents($url);		

			$notfound = strpos($value, "Inga träffar"); //No hit.
			$notfound = ($notfound < 1) ? strpos($value, "Inga träffar") : $notfound;
			if($notfound)
			{
				$value = "";
			}	
			else
			{
                $start = strpos($value, "<span>1.</span>");
                $value = substr($value,$start+15);
                $end = strpos($value, "</h1>");
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
		if($run_param['Search_Type'] >= 1)
		{
			if($debug)
			{
				print "Searching 118700.se business ... ";
			}
			$url="http://mobil.118700.se/companysearch.aspx?q=$thenumber";
			$value = get_url_contents($url);
			$notfound = strpos($value, "Inga träffar"); //No hit.
			$notfound = ($notfound < 1) ? strpos($value, "Inga träffar") : $notfound;
			if($notfound)
			{
				$value = "";
			}	
			else
			{
                $start = strpos($value, "<span>1</span>");
                $value = substr($value,$start+15);
                $end = strpos($value, "</p>");
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
	}
	else
	{
		if($debug)
		{
			print "Skipping Source - Non Swedish number: ".$thenumber."<br>\n";
		}

	}


}
?>