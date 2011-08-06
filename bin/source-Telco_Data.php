<?PHP
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.telcodata.com - These listings are generally only return the geographic location of the caller, not a name.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
    $number_error = false;
    if($debug)
    {
        print "Searching Telco Data ... ";
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
    // check for short number
    if(strlen($thenumber) < 10)
    {
        $number_error = true;
    }


    if(!$number_error)
    {
        $npa = substr($thenumber,0,3);
        $nxx = substr($thenumber,3,3);

        // check for valid area code (201 or higher)
        if ($npa < '201')
        {
            $number_error = true;
        }           
        else
        {
            // Check for Toll-Free numbers (including future TF NPA's)
            $TFnpalist = array(
            "800", "822", "833", "844", "855", "866", "877", "888"
            );
            if(in_array($npa, $TFnpalist))
            {
                $number_error = true;
            }
        }
    }

    if($number_error)
    {
        if($debug)
        {
            print "Skipping Source - Toll Free or International (non-NANP) number: ".$thenumber."<br>\n";
        }
    }
    else
    {
        $url = "http://www.telcodata.us/query/queryexchangexml.html?npa=$npa&nxx=$nxx";
        $value = get_url_contents($url);

        // Telcodata will tell us if we have an invalid NPA/NXX
        $start = strpos($value, "<valid>");
        $valid = substr($value,$start+7);
        $end = strpos($valid, "</valid>");
        $valid = substr($valid,0,$end);

        if ($valid == 'NO')
        {
            if($debug)
            {
                print "Skipping Source - Telcodata reports invalid NPA/NXX: ".$thenumber."<br>\n";
            }
        }
        else
        {

            $start = strpos($value, "<ratecenter>");
            $ratecenter = substr($value,$start+12);
            $end = strpos($ratecenter, "</ratecenter>");
            $ratecenter = substr($ratecenter,0,$end);

            $start = strpos($value, "<state>");
            $state = substr($value,$start+7);
            $end = strpos($state, "</state>");
            $state = substr($state,0,$end);

            $value = $ratecenter.", ".$state ;

            if(strlen($ratecenter) > 1)
            {
                $caller_id = strip_tags($value);
            }
            else if($debug)
            {
                print "not found<br>\n";
            }
        }
    }
}