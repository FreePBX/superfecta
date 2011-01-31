<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "LDAP lookup source.";
$source_param = array();
$source_param['LDAP_Host']['desc'] = 'LDAP Server to search.';
$source_param['LDAP_Host']['type'] = 'text';
$source_param['LDAP_User']['desc'] = 'Authentication Username to connect to the LDAP server';
$source_param['LDAP_User']['type'] = 'text';
$source_param['LDAP_Password']['desc'] = 'Password used to connect to the LDAP server';
$source_param['LDAP_Password']['type'] = 'password';
$source_param['LDAP_Unit']['desc'] = 'Organizational Unit to search.';
$source_param['LDAP_Unit']['type'] = 'text';

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching LDAP for number: {$thenumber}<br>\n";
	}
	// By default, the found name is empty
	$name = "";

	// check if php-ldap is installed
	function_exists('ldap_connect')
	or die ("LDAP functions not available");
 
	// connect
 	$ad=ldap_connect("ldap://{$run_param['LDAP_Host']}")
	or die ("Couldn't connect to {$run_param['LDAP_Host']}");
	
	//Set protocol version
	ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3)
	or die ("Could not set ldap protocol");
	
	// Set this option for AD on Windows Server 2003 per PHP manual
	ldap_set_option($ad, LDAP_OPT_REFERRALS, 0)
	or die ("Could not set option referrals");
	
	// build DC string
	$elements = explode('.', $run_param['LDAP_Host']);
	$dc = "dc=".implode(";dc=", $elements);
	
	// bind
	$bd=ldap_bind($ad,"cn={$run_param['LDAP_User']},{$dc}","{$run_param['LDAP_Password']}")
	or die ("Couldn't bind to {$run_param['LDAP_Host']}");
	
	// search
	$dn = "ou={$run_param['LDAP_Unit']},${dc}";
	if($debug)
	{
		echo "Searching {$dn} ... ";
	}

	if($rs=ldap_search($ad,$dn,"(|(telephoneNumber=*{$thenumber})(mobile=*{$thenumber})(homeTelephoneNumber=*{$thenumber}))",array('cn')))
	{
		if($info = ldap_get_entries($ad, $rs))
		{	
			if($debug)
			{
				echo $info["count"]." entries returned<br>\n";
			}
			if($info["count"] > 0)
			{
				// var_dump($info[0]);
				$name=$info[0]["cn"][0];
			}
		}
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
?>
