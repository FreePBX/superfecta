<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Serbia (Country code 381) searching http://www.telekom.rs/WhitePages/   Works for numbers in the format: (area code including leading 0) + (phone number) minimum of 9 digits.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

if(($usage_mode == 'get caller id') && (!function_exists('cisf_find_area')) && ($debug) )
{
	print "Telekom_rs_SR requires Superfecta v 2.2.4 or greater<br>\n";
}

//run this if the script is running in the "get caller id" usage mode.
if(($usage_mode == 'get caller id') && (function_exists('cisf_find_area')) )
{
	$thenumber_rs = $thenumber;
	$number_error = false;
	$validnpaRS = false;
	if($debug)
	{
		print "Searching http://www.telekom.rs/WhitePages/ ... ";
	}
		
	
	if(strlen($thenumber_rs) < 9)
	{
		$number_error = true;

	}
	if($number_error===false){	
		$npalist_RS = array(
			"Beograd" => "011",
			"Bor" => "030",
			".a.ak" => "032",
			".akovica" => "0390",
			"Gnjilane" => "0280",
			"Jagodina" => "035",
			"Kikinda" => "0230",
			"Kosovska Mitrovica" => "028",
			"Kragujevac" => "034",
			"Kraljevo" => "036",
			"Kru.evac" => "037",
			"Leskovac" => "016",
			"Ni." => "018",
			"Novi Pazar" => "020",
			"Novi Sad" => "021",
			"Pan.evo" => "013",
			"Pe." => "039",
			"Pirot" => "010",
			"Po.arevac" => "012",
			"Prijepolje" => "033",
			"Pri.tina" => "038",
			"Prizren" => "029",
			"Prokuplje" => "027",
			".abac" => "015",
			"Smederevo" => "026",
			"Sombor" => "025",
			"Sremska Mitrovica" => "022",
			"Subotica" => "024",
			"Uro.evac" => "0290",
			"U.ice" => "031",
			"Valjevo" => "014",
			"Vranje" => "017",
			"Zaje.ar" => "019",
			"Zrenjanin" => "023"
		);

		// Check for supported npa
		
		$validnpaRS = cisf_find_area($npalist_RS, $thenumber_rs);
	}

	if($validnpaRS===false)
	{
		$number_error = true;
	}	

	if($number_error)
	{
		if($debug)
		{
			print "Skipping Source - Not a valid or supported number: ".$thenumber_rs."<br>\n";
		}
	}
	else
	{
		$referer="http://www.telekom.rs/WhitePages/SearchPage.asp?Mg=" . $validnpaRS['area_code'];
		$url="http://www.telekom.rs/WhitePages/ResultPage.asp";
		$post_data = array(
			'Telefon'=>$validnpaRS['number'],
			'Ulica'=>'',
			'MG'=>$validnpaRS['area_code'],
			'Ime'=>'',
			'Broj'=>'',
			'Mesto'=>'',
			'Prezime'=>'',
			'submit.x'=>'58',
			'submit.y'=>'10'
		);
		$temp_cookie_file = tempnam("/tmp", "CURLCOOKIE");

		// Load the results page once to get some cookies set
		$value = get_url_contents($url,$post_data,$referer,$temp_cookie_file);
		// Load the results page again
		$value = get_url_contents($url,$post_data,$referer,$temp_cookie_file);
		// Clean up the temp cookie file
		@unlink($temp_cookie_file);

		// Pull the name from the result
		$patternName = "/<img src='images\/linea\.jpg'><\/td><\/tr><TR><TD align='left'><b>(.*?)<\/b>/";

		// Look at named results first
		preg_match($patternName, $value, $names);
		$name = trim(strip_tags($names[1]));
		
		$notfound=false;
		if($name == '')
		{
			$notfound = true;
		}
		
		if($notfound)
		{
			$name="";
		}
		
		if(strlen($name) > 1)
		{
			$caller_id = strip_tags($name);
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
}

?>
