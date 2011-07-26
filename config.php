<?php
if(file_exists("/etc/freepbx.conf")) {
	//This is FreePBX 2.9+
	require("/etc/freepbx.conf");
} elseif(file_exists("/etc/asterisk/freepbx.conf")) {
	//This is FreePBX 2.9+
	require("/etc/asterisk/freepbx.conf");
} else {
	//This is > FreePBX 2.8	
	$functions_location = str_replace("modules/superfecta", "", dirname(__FILE__))."functions.inc.php";
	require_once($functions_location);	
	require_once 'DB.php';
	define("AMP_CONF", "/etc/amportal.conf");
	$amp_conf = parse_amportal_conf(AMP_CONF);
	if(count($amp_conf) == 0) {
		fatal("FAILED");
	}
	$dsn = array(
	    'phptype'  => 'mysql', // Looks like we are assuming mysql  -- is this safe? (jkiel - 01/04/2011)
	    'username' => $amp_conf['AMPDBUSER'],
	    'password' => $amp_conf['AMPDBPASS'],
	    'hostspec' => $amp_conf['AMPDBHOST'],
	    'database' => $amp_conf['AMPDBNAME'],
	);
	$options = array();
	$db =& DB::connect($dsn, $options);
	if(PEAR::isError($db)){
		die($db->getMessage());
	}
}
?>