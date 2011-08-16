<?php
// this file is designed to be used as an include that is part of a loop.


// CID Superfecata lookup to stop a search from progressing.

//  Collect info
	$source_desc = "Artificially sets the CNAM to a user defined value so that no further lookups will be performed.  This can be useful to prevent a lookup from progressing to the next scheme, or to always keep post-processing within the same scheme.  Lookup sources that come after this source, within the same scheme, will be ignored.";
	$source_param['Artificial_CNAM']['desc'] = 'Text string to be used as the CNAM';
	$source_param['Artificial_CNAM']['type'] = 'text';
	$source_param['Artificial_CNAM']['default'] = 'Unknown';



//  run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{

	if($debug)
	{
		print "<br/>Setting CNAM to:".$run_param['artificial']." ...";
	}


	$caller_id = trim($run_param['artificial']);
}
