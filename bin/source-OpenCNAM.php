<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//Last edited Nov. 10, 2012 by lgaetz

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.opencnam.com      This data source returns CNAM data listed at OpenCNAM.";
$source_param = array();
$source_param['POSSA_Notice']['type'] = 'select';
$source_param['POSSA_Notice']['option'][1] = 'OpenCNAM is a finacial contributor to POSSA';
$source_param['POSSA_Notice']['option'][2] = 'A portion of the funds for each preium search';
$source_param['POSSA_Notice']['option'][3] = 'done from Superfecta is contributed to POSSA';
$source_param['POSSA_Notice']['option'][4] = 'for continued development';
$source_param['POSSA_Notice']['default'] = 1;
$source_param['Account_SID']['desc'] = "Enter OpenCNAM Account SID.  If you don't have an account leave this blank to use free service.";
$source_param['Account_SID']['type'] = 'textarea';
$source_param['Account_SID']['default'] = null;
$source_param['Auth_Token']['desc'] = "Enter OpenCNAM user account Auth Token.  If you don't have an account leave this blank to use free service.";
$source_param['Auth_Token']['type'] = 'textarea';
$source_param['Auth_Token']['default'] = null;
$source_param['Ignore_Keywords']['desc'] = 'If this source provides CNAM including any of the keywords listed  here, the CNAM will be ignored and other sources will be used to find the value.<br>
Seperate keywords with commas.';
$source_param['Ignore_Keywords']['type'] = 'textarea';
$source_param['Ignore_Keywords']['default'] = 'unavailable';

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{

    if($debug)
    {
            print 'Searching OpenCNAM..<br> ';
    }

		if ($run_param['Account_SID'] == null or $run_param['Auth_Token'] == null)  //use free url
        {
			$url = "https://api.opencnam.com/v2/phone/" . $thenumber . "?format=pbx";
        }
		else  //use premium url
        {
			$url = "https://api.opencnam.com/v2/phone/" . $thenumber . "?format=pbx&ref=possa&account_sid=".$run_param['Account_SID']."&auth_token=".$run_param['Auth_Token'];
        }
        $sname =  get_url_contents($url);
		$sname = trim(strip_tags($sname));
        if (strlen($sname) > 1)
        {
			// convert list of ignore keywords into array
			$key_words = array();
			$temp_array = explode(',',(isset($run_param['Ignore_Keywords'])?$run_param['Ignore_Keywords']:$source_param['Ignore_Keywords']['default']));
			foreach($temp_array as $val)
			{
				$key_words[] = trim($val);
			}

			if($debug)
			{
				print "found value of $sname ... ";
			}
			// remove all ignore keywords from the retuned CNAM and compare before and after
			$test_string = str_ireplace($key_words,'',$sname);
			if($test_string == $sname)
			{
				$caller_id = $sname;
				if($debug)
				{
					print "determined good.<br>\n";
				}
			}
			else if($debug)
			{
				print " contains flagged key words, returning nothing ";
			}
		
        }
        else if($debug)
        {
                print "not found<br>\n";
        }
    
}
