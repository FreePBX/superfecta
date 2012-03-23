<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber, $thenumber_orig
//Based largely on trunk provided lookup source
//Created, October 28, 2011

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Locates the DID provided by the trunk, and then uses all or some of those digits as a prefix for the Caller ID Name.  This lookup source requires Superfecta Module version 2.2.1 or higher.";
$source_param = array();
$source_param['Filter_Length']['desc'] = 'Number of rightmost digits of returned DID to use for the Caller ID Name or Prefix.  Enter false to disable';
$source_param['Filter_Length']['type'] = 'number';
$source_param['Filter_Length']['default'] = 10;
$source_param['Use_Prefix']['desc'] = 'If enabled, the returned DID digits will be used as a prefix to the Caller ID name.';
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

	//get the DID for this call using the PHP Asterisk Manager
	//first get a list of the active channels and return the first one that matches the caller id value set.
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

        // if there is no DID for the call, asterisk will return (N/A) check for this case and reset
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

		// check if user wants has set the filter length
        	if ($run_param['Filter_Length'] != false)
        	{
                	// keep only the filter_length rightmost digits
        		if (strlen($provided_did) > $run_param['Filter_Length']) $provided_did = substr($provided_did, -$run_param['Filter_Length']);
		}


		//  neet to set $caller_id or CNAM prefix depending on user input
        	if ($run_param['Use_Prefix'] == "on")
                {
                	//  Not sure the following line is a good idea, it works but may interfere with superfecta operation
                	$prefix = $provided_did;
                }
                else
                {
                	$caller_id = $provided_did;
                }

	}
}
