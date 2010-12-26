<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.enumquery.com       This data source returns the user provided extended CNAM data listed in their ENUM (e164) registrations.  Registrants must have specified CNAM data in one of the supported e164 registries for this data source to return a value.<br>See www.enumquery.com for list of supported registries<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
        if($debug)
        {
                print 'Searching EnumQuery..<br> ';
        }

        $loc_number = $thenumber;


                $url = "http://enumquery.com/lookup?e164=1" . $loc_number;
                $sresult =  get_url_contents($url);

                $expr = '/CN=(.*);/isU';
                preg_match_all($expr, $sresult, $sname);
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
?>