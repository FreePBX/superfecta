<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber, $thenumber_orig
//Based largely on trunk provided lookup source
//Revised, October 28, 2011

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Locates the DID provided by the trunk, and then uses all or some of those digits as a prefix for the Caller ID Name.  This lookup source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();
$source_param['Filter_Length']['desc'] = 'Number of rightmost digits of DID to use.  Enter false to disable';
$source_param['Filter_Length']['type'] = 'number';
$source_param['Filter_Length']['default'] = 10;
$source_param['Use_Prefix']['desc'] = 'Use returned DID digits as a Name prefix';
$source_param['Use_Prefix']['type'] = 'checkbox';
$source_param['Use_Prefix']['default'] = "on";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Looking for Trunk Provided DID ... ";
	}

	$provided_did = '';

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
					if(strpos($val2,'DNID Digits: ') !== false)
					{
						$provided_did = trim(str_replace('DNID Digits: ','',$val2));
						break;
					}
				}

				//break out if the value is set.
				if($provided_did != '')
				{
					break;
				}
			}
		}
	}

        // if no DID is set, asterisk will return (N/A) check for this case and reset
	if ($provided_did == '(N/A)')
        {
        	$provided_did = '';
        }

	if($debug && ($provided_did == ''))
	{
		print "not found<br>\n";
	}
	else if($provided_did != '')
	{
		if($debug)
		{
			print "found DID of $provided_did ... ";
		}

//  need to trim the DID to the $run_param['Filter_Length'] if specified
//  neet to set $caller_id or CNAM prefix depending on user input $run_param['use_prefix']

	}
}
