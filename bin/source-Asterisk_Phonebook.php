<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Searches the built in Asterisk Phonebook for caller ID information. This is a very fast source of information.<br>If you are caching to the phonebook, this should be the first lookup source.";


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching Asterisk Phonebook ... ";
	}
	
	$value = $astman->database_get('cidname',$thenumber);
	
	if($value)
	{
		$caller_id = $value;
	}
	else if($debug)
	{
		print "not found<br>\n";
	}
}
?>