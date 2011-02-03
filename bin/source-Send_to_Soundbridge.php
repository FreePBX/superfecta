<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This source will send the number and the Caller ID to a Roku Soundbridge.<br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();
$source_param['Server_address']['desc'] = 'Specify the Soundbridge IP address.';
$source_param['Server_address']['type'] = 'text';
$source_param['Server_address']['default'] = '';
$source_param['Font_size']['desc'] = "Specify the font size required.<br><br>Valid values are<br><pre>
1  - Fixed8
2  - Fixed16
3  - ZurichBold32
10 - ZurichBold16
11 - ZurichLite16
12 - Fixed16
14 - SansSerif16</pre>";
$source_param['Font_size']['type'] = 'select';
$source_param['Font_size']['option'][1] = 'Fixed 8';
$source_param['Font_size']['option'][2] = 'Fixed 16';
$source_param['Font_size']['option'][3] = 'ZurichBold 32';
$source_param['Font_size']['option'][4] = 'ZurichBold 16';
$source_param['Font_size']['option'][5] = 'ZurichLite 16';
$source_param['Font_size']['option'][6] = 'Fixed 16';
$source_param['Font_size']['option'][7] = 'SansSerif 16';
$source_param['Font_size']['default'] = '1';
$source_param['Display_time']['desc'] = 'Specify how many seconds to display the CID for.';
$source_param['Display_time']['type'] = 'text';
$source_param['Display_time']['default'] = '2';
		
if($usage_mode == 'post processing')
{
	if (($run_param['Server_address'] !='') && ($first_caller_id != ''))
	{
		// Fonts supported by sketch
		$fonts = array( '1'=>1, 	// Fixed 8
						'2'=>2, 	// Fixed 16
						'3'=>3, 	// Zurich Bold 32
						'4'=>10, 	// Zurich Bold 16
						'5'=>11, 	// Zurich Lite 16
						'6'=>12, 	// Fixed 16
						'7'=>14 );	// Sans Serif 16
		
		// Validate font size
		if(array_key_exists($run_param['Font_size'], $fonts) )
		{
			$font = $fonts[ $run_param['Font_size'] ];
		}
		else
		{
		    die("Invalid Font size requested.");
		}
		
		$errno='';
		$errstr='';
		
		// connect to the SoundBridge
		$fRoku = fsockopen($run_param['Server_address'], 4444, $errno, $errstr, 10) or die ("Could not connect to Roku SoundBridge");
		stream_set_blocking($fRoku, 0);
		usleep(100000);

		// Display message
		fwrite($fRoku,"sketch\n");
		usleep(100000);
		
		// Set font size 
		fwrite($fRoku,"font {$font}\n");
		
		// Center text on display
		fwrite($fRoku,"text c c \"{$first_caller_id} ({$thenumber})\"\n");
		sleep($run_param['Display_time']);
	
		// Clear the screen		
		fwrite($fRoku,"quit\n");
		usleep(100000);
		
		// Close connection
		fwrite($fRoku,"exit\n");
		usleep(100000);
		fclose($fRoku);
		
		if($debug)
		{
			print "Send to Soundbridge: \"{$first_caller_id} ({$thenumber})\"<br><br>";
		}
	}
}

?>