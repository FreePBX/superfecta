<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.freecnam.org       This data source returns CNAM data listed at FreeCNAM.";
$source_param = array();


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;
    if($debug)
    {
            print 'Searching FreeCNAM..<br> ';
    }

	//check for the correct 11 digits in US/CAN phone numbers in international format.
	// country code + number
	if (strlen($thenumber) == 11)
	{
		if (substr($thenumber,0,1) == 1)
		{
			$thenumber = substr($thenumber,1);
		}
		else
		{
			$number_error = true;
		}

	}
	// international dialing prefix + country code + number
	if (strlen($thenumber) > 11)
	{
		if (substr($thenumber,0,3) == '001')
		{
			$thenumber = substr($thenumber, 3);
		}
		else
		{
			if (substr($thenumber,0,4) == '0111')
			{
				$thenumber = substr($thenumber,4);
			}			
			else
			{
				$number_error = true;
			}
		}

	}	
	
	// number
	if(strlen($thenumber) < 10)
	{
		$number_error = true;
	}
	
	if(!$number_error)
	{
		$thenumber = (substr($thenumber,0,1) == 1) ? substr($thenumber,1) : $thenumber;
        $url = "http://freecnam.org/dip?q=" . $thenumber;
        $sname =  get_url_contents($url);

        if (strlen($sname) > 1)
        {
			$caller_id = strip_tags($sname);
        }
        else if($debug)
        {
                print "not found<br>\n";
        }
	}
}
?>