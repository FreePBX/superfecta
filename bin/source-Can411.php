<?php
$source_desc = "http://www.411.ca -     These listings include business and residential data for Canada.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";
if($usage_mode == 'get caller id')
{
        $number_error = false;
	$validnpaCAN = false;
        $found = false;
        $found = "";

        if($debug)
        {
                print "Searching 411.ca ... ";
        }
        //check for the correct 11 digits NANP phone numbers in international format.
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
                $npa = substr($thenumber,0,3);
                // Check for valid CAN NPA
                $npalistCAN = array(
                        "204", "226", "249", "250", "289", "306", "343", "365", "403", "416", "418", "438",
                        "450", "506", "514", "519", "579", "581", "587", "604", "613", "647",
                        "705", "709", "778", "780", "807", "819", "867", "873", "902", "905",
                        "800", "866", "877", "888"
                        );
                if(in_array($npa, $npalistCAN))
                {
                        $validnpaCAN = true;
                }
        }
        if(!$validnpaCAN)
        {
                $number_error = true;
        }
        if($number_error)
        {
                if($debug)
                {
                        print "Skipping Source - Non Canadian number: ".$thenumber."<br>\n";
                }
        }
        else
        {
                if (strlen($thenumber) == 10)
                {
                        $url="http://www.411.ca/whitepages/index.html?n=" . $thenumber;
                        $sresult =  get_url_contents($url);
                        $expr = "/location.href = \'(.*)\';" . chr(13) . "/Uis";
                        preg_match_all($expr, $sresult, $surl2);
                        if (count($surl2[1]) == 1)
                        {
                               $found = true;
// When it's business the http://www.411.ca/ is present in the url already, so I look for it and add it in case it's not there
                                $expr = '/http:\/\/(.*)/Uis';
                                preg_match_all($expr, $surl2[1][0], $scheckurl);
                                if (count($scheckurl[1]) == 0)
                                {
                                        $url = "http://www.411.ca/" . $surl2[1][0];
                                } else
                                {
                                        $url = $surl2[1][0];
                                }
                                $sresult =  get_url_contents($url);
                                $expr = '/<div class="name">(.*)<\/div>/Uis';
                                preg_match_all($expr, $sresult, $sname);
// Business result have a different div code.
                                if ($sname[1][0] == "")
                                {
                                        $expr = '/<div class="org fn" property="v:name">(.*)<\/div>/Uis';
                                        preg_match_all($expr, $sresult, $sname);
                                }
 
                        } else
                        {
                                $expr = '/<div class="name"><a href="(.*)">/Uis';
                                preg_match_all($expr, $sresult, $surl2);
                                if (count($surl2[1]) > 1)
                                {
                                        $found = true;
                                        $url = "http://www.411.ca/" . $surl2[1][0];
                                        $sresult =  get_url_contents($url);
                                        $expr = '/<div class="name">(.*)<\/div>/Uis';
                                        preg_match_all($expr, $sresult, $sname);
                                }else
                                {
                                        $found = false;
                                }
                        }
 
                        if ($found)
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
        }
}
?>
