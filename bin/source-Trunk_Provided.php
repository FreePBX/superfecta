<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Locates the Caller ID Name provided by the trunk, and then decides based on a list of key words if the provided name should be used.<br><br>This data source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();
$source_param['Ignore_Keywords']['desc'] = 'If the trunk provided caller id includes any of the keywords listed  here, the trunk provided value will be ignored and other sources will be used to find the value.<br>
Seperate keywords with commas.';
$source_param['Ignore_Keywords']['type'] = 'textarea';
$source_param['Ignore_Keywords']['default'] = 'unknown, toll free, unlisted, (N/A)';
$source_param['Discard_Numeric']['desc'] = 'Enable this setting to discard trunk provided CNAM that is all digits.';
$source_param['Discard_Numeric']['type'] = 'checkbox';
$source_param['Discard_Numeric']['default'] = 'on';

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Looking for Trunk Provided Caller ID ... ";
	}

	$key_words = array();
	$temp_array = explode(',',(isset($run_param['Ignore_Keywords'])?$run_param['Ignore_Keywords']:$source_param['Ignore_Keywords']['default']));
	foreach($temp_array as $val)
	{
		$key_words[] = trim($val);
	}
	$provided_caller_id = '';

	//get the caller id name already set for this call using the PHP Asterisk Manager
	//first get a list of the active channels and return the first one that has a caller id value set.
	$value = $astman->command('core show channels concise');
	$chan_array = preg_split("/\n/",$value['data']);
	foreach($chan_array as $val)
	{
		$this_chan_array = explode("!",$val);
		if(isset($this_chan_array[7]))
		{
                        if ($thenumber_orig == $this_chan_array[7])
			{
				$value = $astman->command('core show channel '.$this_chan_array[0]);
				$this_array = preg_split("/\n/",$value['data']);
				foreach($this_array as $val2)
				{
					if(strpos($val2,'Caller ID Name: ') !== false)
					{
						$provided_caller_id = trim(str_replace('Caller ID Name: ','',$val2));
						break;
					}
				}

				//break out if the value is set.
				if($provided_caller_id != '')
				{
					break;
				}
			}
		}
	}

	//  Check to see if the CNAM is the same as the CID and discard
	if ($provided_caller_id == $thenumber_orig)
	{
	$provided_caller_id='';
		if($debug)
		{
		print "CID name is the same as CID number<br>\n";
		}
	}

	//  Check to see if the CNAM is all numeric and discard if user enables
	if (($run_param['Discard_Numeric'] == 'on') && (is_numeric($provided_caller_id)))
	{
		$provided_caller_id='';
		if($debug)
		{
		print "CID is all numeric - discarded<br>\n";
		}
        }
	if($debug && ($provided_caller_id == ''))
	{
		print "not found<br>\n";
	}
	else if($provided_caller_id != '')
	{
		if($debug)
		{
			print "found value of $provided_caller_id ... ";
		}
		$test_string = str_ireplace($key_words,'',$provided_caller_id);
		if($test_string == $provided_caller_id)
		{
			$caller_id = $provided_caller_id;
			if($debug)
			{
				print "determined good.<br>\n";
			}
		}
		else if($debug)
		{
			print " contains flagged key words, returning nothing";
		}
	}
}
