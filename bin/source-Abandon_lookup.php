<?php
// this file is designed to be used as an include that is part of a loop.


// CID Superfecata lookup to stop a search from progressing.

//  Collect info
	$source_desc = "Artificially sets the CNAM to a user defined value so that no further lookups will be performed";
	$source_param['Artificial_CNAM']['desc'] = 'Text string to be used as the CNAM';
	$source_param['Artificial_CNAM']['type'] = 'text';
	$source_param['Artificial_CNAM']['default'] = 'Unknown';



//  run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if ($run_param['Artificial_CNAM'] == NULL)
        {
		$run_param['Artificial_CNAM'] = $source_param['Artificial_CNAM']['default'];
        }

	if($debug)
	{
		print "<br/>Setting CNAM to:".$run_param['Artificial_CNAM']." ...";
	}


	$caller_id = trim($run_param['Artificial_CNAM']);
}