<?php
// this file is designed to be used as an include that is part of a loop.


// CID Superfecata lookup to stop a search from progressing.

//  Collect info
	$source_desc = "Artificially sets the CNAM to a user defined value so that no further lookups will be performed";
	$source_param['artificial']['desc'] = 'Text string to be used as the CNAM';
	$source_param['artificial']['type'] = 'text';
	$source_param['artificial']['default'] = 'Unknown';



//  run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{

	if($debug)
	{
		print "<br/>Setting CNAM to:".$run_param['artificial']." ...";
	}


	$caller_id = trim($run_param['artificial']);
}
