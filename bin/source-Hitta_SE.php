<?php
//by pbxinaflash.com forum's user Nixi.
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.hitta.se - This listing includes data from the Swedish Hitta.se directory.";
$source_param = array();
$source_param['Search_Type']['desc'] = 'Select which sources you want to search';
$source_param['Search_Type']['type'] = 'select';
$source_param['Search_Type']['option'][1] = 'Residential';
$source_param['Search_Type']['option'][2] = 'Business';
$source_param['Search_Type']['option'][3] = 'Residential & Business';
$source_param['Search_Type']['default'] = 3;


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching hitta.se ... ";
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
				print "Searching Hitta.se residential ... ";
			}
		
			$url="http://wap.hitta.se/default.aspx?Who=$thenumber&Where=&PageAction=White";
			$value = get_url_contents($url);		

			$notfound = strpos($value, "<a href=\"/Details"); //No information found
			$notfound = ($notfound < 1); 
			if($notfound)
			{
				$value = "";
			}	
			else
			{
			$start = strpos($value, "<a href=\"/Details");
			$value = substr($value,$start+17);
			$start = strpos($value, ">");
			$value = substr($value,$start+3);
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
		if($run_param['Search_Type'] >= 1)
		{
			if($debug)
			{
				print "Searching Hitta.se business ... ";
			}
		
			$url="http://wap.hitta.se/default.aspx?Who=$thenumber&Where=&PageAction=Pink";
			$value = get_url_contents($url);
	
			$notfound = strpos($value, "Ingen exakt"); //No direct match. Ignore suggestions.
			$notfound = ($notfound < 1) ? strpos($value, "Ingen exakt") : $notfound;
	
			if($notfound)
			{
				$value = "";
			}	
			else
			{
				$start = strpos($value, "<a href=\"/Details");
				$value = substr($value,$start+17);
				$start = strpos($value, ">");
				$value = substr($value,$start+3);
				$end = strpos($value, "</a>");
				$value = substr($value,0,$end);
			
			}
	
			$notfound = strpos($value, "tta.se"); //nothing found.
			$notfound = ($notfound < 1) ? strpos($value, "tta.se") : $notfound;
			if($notfound)
			{
				$value = "";
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
