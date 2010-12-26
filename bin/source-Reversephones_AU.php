<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber 
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "http://reversephones.com.au - 	These listings include residential data for AU.<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

$source_param['Premium_Key']['desc'] = 'Your Premium Key for reversephones.com.au.';
$source_param['Premium_Key']['type'] = 'text';
$source_param['Premium_Key']['default'] = '';

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$number_error = false;

	if($debug)
	{
		print "Searching reversephones.com.au ... ";
	}
	
	// Validate number
	if($match = match_pattern("0[2356789]XXXXXXXX",$thenumber)){
		// Land line
		$num1 = substr($thenumber,0,2);
		$num2 = substr($thenumber,2,4);
		$num3 = substr($thenumber,6,4);
		$fullnum = "(".$num1.") ".$num2." ".$num3;


	}elseif($match = match_pattern("04XXXXXXXX",$thenumber)){
		// Mobile number
		$num1 = substr($thenumber,0,4);
		$num2 = substr($thenumber,4,3);
		$num3 = substr($thenumber,7,3);
		$fullnum = $num1." ".$num2." ".$num3;
	}else{
		$number_error = true;
	}
	
	if($number_error)
	{
		if($debug)
		{
			print "Skipping Source - Non AU number: ".$thenumber."<br>\n";
		}
	}
	else
	{
		// Search reversephones.com.au
		// Download as CSV
		$url = "http://reversephones.com.au/csp?csv=&surname=&initials=&street_number=&street_name=&street_type=&locality=&state=&phone_number=$thenumber&key=".(isset($run_param['Premium_Key'])?$run_param['Premium_Key']:'')."&terms=1";
		$value = get_url_contents($url);
		//$value = file_get_contents('/tmp/results.csv'); // For debug
		// No name, unless we find one
		$name = "";

		// Convert csv file to array
		// It would be nice to use str_getcsv and other PHP5 functions here, 
		// but we need to support older versions of php
		$csv_line_array =  preg_split("/\n(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", trim($value));
		$csv_array = array();
		$highest_period = 0;
		$highest_period_line = 0;
		foreach($csv_line_array as $line_key => $csv_line){
			$csv_array_line = preg_split("/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", trim($csv_line)); 
			// Again we'll grumble about not having PHP5 funtions to use, and instead do it the hard way...
			if($line_key==0){
				// Cleap up the column names
				foreach($csv_array_line as $key => $value){
					$csv_array_line[$key] = stripslashes(trim(trim(trim($value),'"')));
				}
				$csv_array_keys = array_flip($csv_array_line);
			}else{
				$csv_new_line = array();
				$i = 0;
				foreach ($csv_array_keys as $key => $value){
					$cell_value = stripslashes(trim(trim(trim($csv_array_line[$i]),'"')));
					if($key=='Period'){
						$larray = explode("to",$cell_value);
						// Find the result with the highest date
						if(isset($larray[count($larray)-1]) && (intval($larray[count($larray)-1]) > $highest_period)){
							$highest_period = intval($larray[count($larray)-1]);
						}
					}
					$csv_new_line[$key] = $cell_value;
					$i ++;
				}
				$csv_array[] = $csv_new_line;
			}
		}
		// Return the result we found with the most recent period date
		if($highest_period){
			$name = trim($csv_array[$highest_period_line]['Surname / Company name']) . " " . trim($csv_array[$highest_period_line]['Initials / Business category']);
		}

		// If we found a match, return it
		if(strlen($name) > 1)
		{
			$caller_id = $name;
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
}
?>
