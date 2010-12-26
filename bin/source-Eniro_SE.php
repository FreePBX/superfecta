<?php
//by pbxinaflash.com forum's user Nixi
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retrieve website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.enrio.se - This listing includes data from the Swedish Eniro.se directory.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;
	if($debug)
	{
		print "Searching Eniro.se ... ";
	}
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
		$url="http://mobil.eniro.se/query?search_word=$thenumber&geo_area=&what=moball";
		$value = get_url_contents($url);
		{
			$notfound = strpos($value, "<div class=\"hitsTxt\">0"); //No information found
			$notfound = ($notfound > 0); 
			if($notfound)
			{
				$value = "";
			}	
			else
			{
				$foundmany = strpos($value, "<div class=\"catHead\">"); //More than one result found. Select number 1.
				$foundmany = ($foundmany > 1); 

				if($foundmany)
				{
					//There can be two alternative pages when many are found
					$foundmany2 = strpos($value, "<span class=\"gray\">");
					$foundmany2 = ($foundmany > 0); 
					if($foundmany2)
					{
						$start = strpos($value, "<span class=\"gray\">");
						$value = substr($value,$start+19);
						$end = strpos($value, "</span>");
						$value = substr($value,0,$end);
					}
				}	
				else
				{
					$foundBusiness = strpos($value, "<div class=\"header\"> </div>");
					$foundBusiness = ($foundBusiness > 0); 
                    if($foundBusiness)
					{
                        $start = strpos($value, "<div class=\"gCont\">");
						$value = substr($value,$start+19);
                        $start = strpos($value, "<div>");
						$value = substr($value,$start+5);
                        $end = strpos($value, "</div>");
                        $value = substr($value,0,$end);
                    }
                    else //personal number
                    {
                        $start = strpos($value, "<div class=\"gCont\">");
                        $value = substr($value,$start+19);
                        $start = strpos($value, "<div class=\"header\">");
                        $value = substr($value,$start+20);
                        $end = strpos($value, "</div>");
                        $value = substr($value,0,$end);
                    }
				}
			}				
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
	else
	{
		if($debug)
		{
			print "Skipping Source - Non Swedish number: ".$thenumber."<br>\n";
		}

	}
}
?>