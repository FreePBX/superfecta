<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.nummerchecker.com - This source includes business and residential data for The Netherlands<br><br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
        if($debug)
        {
                print 'Searching NummerChecker...<br> ';
        }
        //NL 10

        $loc_number = $thenumber;
        $adddecode= false;

        // Test for NL
        if (strlen($loc_number) > 10)
        {
                if (substr($loc_number,0,2)=='31')
                {
                        $loc_number = '0'.substr($loc_number,2);
                }
                else if (substr($loc_number,0,4)=='0031')
                {
                        $loc_number = '0'.substr($loc_number,4);
                }
                else if (substr($loc_number,0,5)=='01131')
                {
                        $loc_number = '0'.substr($loc_number,5);
                }

        }

        if (strlen($loc_number) == 10)
        {
                $url = "http://nummerchecker.com/result.php?input_telefoon=" . $loc_number;
                $sresult =  get_url_contents($url);

                $expr = '/<td class="result_colom" colspan="2">(.*)<\/td>/isU';
                preg_match_all($expr, $sresult, $sname);
                if (count($sname[1]) > 6)
                {
                        $sname = $sname[1][6];
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
        } else if($debug)
        {
                print "only for the netherlands !!<br>\n";
        }
}
?>