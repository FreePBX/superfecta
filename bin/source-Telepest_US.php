<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://www.telepest.us - A datasource devoted to identifying telemarketers. All information on this site is submitted by users. The operators of Telepest make no claims whatsoever regarding its accuracy or reliability.";
//$source_param = array();
//$source_param['Username']['desc'] = 'Your user account Login on the Telepest.us web site.';
//$source_param['Username']['type'] = 'text';
//$source_param['Password']['desc'] = 'Your user account Password on the Telepest.us web site.';
//$source_param['Password']['type'] = 'password';
//$source_param['Report_Back']['desc'] = 'If a valid caller id name is found, provide it back to Telepest for their database.';
//$source_param['Report_Back']['type'] = 'checkbox';


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;
      if($debug)
	{
		print "Searching Telepest.us ... ";
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
		$npa = substr($thenumber,0,3);
				
		// Check for valid US NPA
		$npalistUS = array(
			"201", "202", "203", "205", "206", "207", "208", "209", "210", "212",
			"213", "214", "215", "216", "217", "218", "219", "224", "225", "228",
			"229", "231", "234", "239", "240", "242", "246", "248", "251", "252",
			"253", "254", "256", "260", "262", "264", "267", "268", "269", "270",
			"276", "281", "284", "301", "302", "303", "304", "305", "307", "308",
			"309", "310", "312", "313", "314", "315", "316", "317", "318", "319",
			"320", "321", "323", "325", "330", "331", "334", "336", "337", "339",
			"340", "345", "347", "351", "352", "360", "361", "386", "401", "402",
			"404", "405", "406", "407", "408", "409", "410", "412", "413", "414",
			"415", "417", "419", "423", "424", "425", "430", "432", "434", "435",
			"440", "441", "443", "456", "469", "473", "478", "479", "480", "484",
			"500", "501", "502", "503", "504", "505", "507", "508", "509", "510",
			"512", "513", "515", "516", "517", "518", "520", "530", "540", "541",
			"551", "559", "561", "562", "563", "567", "570", "571", "573", "574",
			"575", "580", "585", "586", "600", "601", "602", "603", "605", "606",
			"607", "608", "609", "610", "612", "614", "615", "616", "617", "618",
			"619", "620", "623", "626", "630", "631", "636", "641", "646", "649",
			"650", "651", "660", "661", "662", "664", "670", "671", "678", "682",
			"684", "700", "701", "702", "703", "704", "706", "707", "708", "710",
			"712", "713", "714", "715", "716", "717", "718", "719", "720", "724",
			"727", "731", "732", "734", "740", "754", "757", "758", "760", "762",
			"763", "765", "767", "769", "770", "772", "773", "774", "775", "779",
			"781", "784", "785", "786", "787", "801", "802", "803", "804", "805",
			"806", "808", "809", "810", "812", "813", "814", "815", "816", "817",
			"818", "828", "829", "830", "831", "832", "843", "845", "847", "848",
			"850", "856", "857", "858", "859", "860", "862", "863", "864", "865",
			"868", "869", "870", "876", "878", "900", "901", "903", "904", "906",
			"907", "908", "909", "910", "912", "913", "914", "915", "916", "917",
			"918", "919", "920", "925", "928", "931", "936", "937", "939", "940",
			"941", "947", "949", "951", "952", "954", "956", "970", "971", "972",
			"973", "978", "979", "980", "985", "989",
			"800", "866", "877", "888"
		);
		
		$validnpaUS = false;
		if(in_array($npa, $npalistUS))
		{
			$validnpaUS = true;
		}
		
		// Check for valid CAN NPA
		$npalistCAN = array(
			"204", "226", "250", "289", "306", "403", "416", "418", "438", "450",
			"506", "514", "519", "581", "587", "604", "613", "647", "705", "709",
			"778", "780", "807", "819", "867", "902", "905",
			"800", "866", "877", "888"
		  );
		
		$validnpaCAN = false;
		if(in_array($npa, $npalistCAN))
		{
			$validnpaCAN = true;
		}
	}

	if((!$validnpaUS && !$validnpaCAN))
	{
		$number_error = true;
	}
	
	if($number_error)
	{
		if($debug)
		{
			print "Skipping Source - Toll Free or Non US/CAN number: ".$thenumber."<br>\n";
		}
	}
	else
	{
		$url = "http://www.telepest.us/$thenumber";
		$value = get_url_contents($url);
		
		$start = strpos($value, $thenumber.' is not in our database of possible telepests');
		if($start >0)
		{
			$caller_id=''; // Not a telepest
			if($debug)
			{
				print "not found<br>\n";
			}
		}
		else
		{
			$start = strpos($value, $thenumber.' has been reported as a possible telepest');
			if($start >0)
			{
				$spam = true;	// Reported as a telepest

				$start = strpos($value, 'We have no information on the ownership of this number.');
				if($start == false)
				{
					$start = strpos($value, 'According to reports, this number is most likely to belong to');
					$value = substr($value,$start+62);
					$end = strpos($value,'</p>');
					$value = substr($value,0, $end);
					$caller_id = strip_tags($value);
				}
				else
				{
					$caller_id = '';
				}
			}
		}
	}
}
if($usage_mode == 'post processing')
{
	//return the value back to Telepest if the user has enabled it and the result didn't come from cache. This will truncate the string to 15 characters
	if((($winning_source != 'Telepest_US') && ($first_caller_id != '') && ($spam == '1') && ($run_param['Report_Back'] == 'on')))
	{
	$reportbacknow = true;
	}	
	else
	{
	$reportbacknow = false;
	}	
	if ($reportbacknow) 
	{
//		$url = "http://telepest.us/handlers/pestreport.php?action=\"File Report\"&name=".$source_param['Username']."&pass=".$source_param['Password']."&phoneNumber=$thenumber&date=".date('Y-m-d')."&callerID=".urlencode(substr($first_caller_id,0,15));
		$value = get_url_contents($url);
		if($debug)
		{
			$st_success = strstr($value, "success");
			$st_error = strstr($value, "errorMsg");
			$success = substr($st_success,8,1);
			$error = substr($st_error,9);
			if($success=='1')
			{
				print "Success. Reported SPAM caller back to Telepest_US.com.<br>\n<br>\n";
			}
			else
			{
				print "Failed reporting back to Telepest_US.com with error message: ".$error.".<br>\n<br>\n";
			}
		}
	}
}
?>
