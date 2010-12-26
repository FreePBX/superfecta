<?php 
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//this data source created by Tony Shiffer  11/17/09
//upgraded for 5 sources by MyKroft on 08/13/10

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "This send-only source sends the number and the CNAM to the companion Windows Winunciator program.<br>You may configure up to 5 Winunciator installations at once.<br>This datasource should be one of the last data sources on your list, as it does not provide any data of its own, and can only send what information has been collected before it is run.<br><br>This data source requires Superfecta Module version 2.2.3 or higher.";
$source_param = array();
$source_param['URL_address_1']['desc'] = 'Specify the Enter the IP/machine name/FQDN of the windows machine running Winunciator. (Example: 192.168.0.99)';
$source_param['URL_address_1']['type'] = 'text';
$source_param['URL_address_1']['default'] = '';
$source_param['URL_port_1']['desc'] = 'Specify the PORT to the Winunciator installation. (Example: 8080)';
$source_param['URL_port_1']['type'] = 'text';
$source_param['URL_port_1']['default'] = '8080';
$source_param['URL_address_2']['desc'] = 'Specify the Enter the IP/machine name/FQDN of the windows machine running Winunciator. (Example: 192.168.0.99)';
$source_param['URL_address_2']['type'] = 'text';
$source_param['URL_address_2']['default'] = '';
$source_param['URL_port_2']['desc'] = 'Specify the PORT to the Winunciator installation. (Example: 8080)';
$source_param['URL_port_2']['type'] = 'text';
$source_param['URL_port_2']['default'] = '8080';
$source_param['URL_address_3']['desc'] = 'Specify the Enter the IP/machine name/FQDN of the windows machine running Winunciator. (Example: 192.168.0.99)';
$source_param['URL_address_3']['type'] = 'text';
$source_param['URL_address_3']['default'] = '';
$source_param['URL_port_3']['desc'] = 'Specify the PORT to the Winunciator installation. (Example: 8080)';
$source_param['URL_port_3']['type'] = 'text';
$source_param['URL_port_3']['default'] = '8080';
$source_param['URL_address_4']['desc'] = 'Specify the Enter the IP/machine name/FQDN of the windows machine running Winunciator. (Example: 192.168.0.99)';
$source_param['URL_address_4']['type'] = 'text';
$source_param['URL_address_4']['default'] = '';
$source_param['URL_port_4']['desc'] = 'Specify the PORT to the Winunciator installation. (Example: 8080)';
$source_param['URL_port_4']['type'] = 'text';
$source_param['URL_port_4']['default'] = '8080';
$source_param['URL_address_5']['desc'] = 'Specify the Enter the IP/machine name/FQDN of the windows machine running Winunciator. (Example: 192.168.0.99)';
$source_param['URL_address_5']['type'] = 'text';
$source_param['URL_address_5']['default'] = '';
$source_param['URL_port_5']['desc'] = 'Specify the PORT to the Winunciator installation. (Example: 8080)';
$source_param['URL_port_5']['type'] = 'text';
$source_param['URL_port_5']['default'] = '8080';


if($usage_mode == 'post processing')
{
	if ($run_param['URL_address_1'] && $run_param['URL_port_1'] != '')
	{
		$value = get_url_contents($run_param['URL_address_1'] . ':' . $run_param['URL_port_1'] . '/' . $first_caller_id . '|' . $thenumber);
		if($debug)
			{
			print 'Send to Winunciator: ' . $run_param['URL_address_1'] . ':' . $run_param['URL_port_1'] . '/' . $first_caller_id . '|' . $thenumber .'<br><br>';
			}
	}
	if ($run_param['URL_address_2'] && $run_param['URL_port_2'] != '')
	{
		$value = get_url_contents($run_param['URL_address_2'] . ':' . $run_param['URL_port_2'] . '/' . $first_caller_id . '|' . $thenumber);
		if($debug)
			{
			print 'Send to Winunciator: ' . $run_param['URL_address_2'] . ':' . $run_param['URL_port_2'] . '/' . $first_caller_id . '|' . $thenumber .'<br><br>';
			}
	}
	if ($run_param['URL_address_3'] && $run_param['URL_port_3'] != '')
	{
		$value = get_url_contents($run_param['URL_address_3'] . ':' . $run_param['URL_port_3'] . '/' . $first_caller_id . '|' . $thenumber);
		if($debug)
			{
			print 'Send to Winunciator: ' . $run_param['URL_address_3'] . ':' . $run_param['URL_port_3'] . '/' . $first_caller_id . '|' . $thenumber .'<br><br>';
			}
	}
	if ($run_param['URL_address_4'] && $run_param['URL_port_4'] != '')
	{
		$value = get_url_contents($run_param['URL_address_4'] . ':' . $run_param['URL_port_4'] . '/' . $first_caller_id . '|' . $thenumber);
		if($debug)
			{
			print 'Send to Winunciator: ' . $run_param['URL_address_4'] . ':' . $run_param['URL_port_4'] . '/' . $first_caller_id . '|' . $thenumber .'<br><br>';
			}
	}
	if ($run_param['URL_address_5'] && $run_param['URL_port_5'] != '')
	{
		$value = get_url_contents($run_param['URL_address_5'] . ':' . $run_param['URL_port_5'] . '/' . $first_caller_id . '|' . $thenumber);
		if($debug)
			{
			print 'Send to Winunciator: ' . $run_param['URL_address_5'] . ':' . $run_param['URL_port_5'] . '/' . $first_caller_id . '|' . $thenumber .'<br><br>';
			}
	}

}
?>