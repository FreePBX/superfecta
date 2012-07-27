<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
// revised July 27, 2012

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.opencnam.com      This data source returns CNAM data listed at OpenCNAM.";
$source_param = array();
$source_param['Username']['desc'] = "Enter OpenCNAM user account name.  If you don't have an account leave this blank to use free service.";
$source_param['Username']['type'] = 'text';
$source_param['Username']['default'] = null;
$source_param['API']['desc'] = "Enter OpenCNAM user account API.  If you don't have an account leave this blank to use free service.";
$source_param['API']['type'] = 'text';
$source_param['API']['default'] = null;


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
    $number_error = false;
    if($debug)
    {
            print 'Searching OpenCNAM..<br> ';
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

    if(strlen($thenumber) < 10)
    {
        $number_error = true;
    }

    $thenumber = (substr($thenumber,0,1) == 1) ? substr($thenumber,1) : $thenumber;

    if(!$number_error)
    {
    	if ($run_param['Username'] == null or $run_param['API'] == null)  //use free url
        {
			$url = "https://api.opencnam.com/v1/phone/" . $thenumber . "?format=text";
        }
		else  //use premium url
        {
			$url = "https://api.opencnam.com/v1/phone/" . $thenumber . "?format=text&username=".$run_param['Username']."&api_key=".$run_param['API'];
        }

        $sname =  get_url_contents($url);

	// check for returned CNAM of "Currently running a lookup for phone..."
	$pattern = "/Currently running a lookup for phone.*/";
	preg_match($pattern, $sname, $result);
	if ($result[0])
	{
		$sname = "";
		if($debug)
		{
			print "Lookup pending, no useful data<br>\n";
		}
	}

        if (strlen($sname) > 1)
        {
            $caller_id = trim(strip_tags($sname));
        }
        else if($debug)
        {
                print "not found<br>\n";
        }
    }
}
