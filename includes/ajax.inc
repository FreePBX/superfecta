<?php
require_once("superfecta_base.php");
$superfecta = new superfecta_base;
require_once('JSON/JSON.php');

$root_dir = dirname(dirname(__FILE__));

$out = array("success" => false);
global $db;

switch($_REQUEST['type']) {
    case "display_debug":
        echo "ok";
        die();
        break;
    case "update_schemes":
        $scheme_list = json_decode($_REQUEST['data']);
        $order = 1;
        foreach($scheme_list as $data) {
            $data = preg_replace('/^scheme_/i', '', $data);
            $sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES('".$db->escapeSimple($order)."', '".$db->escapeSimple($data)."', 'order')";
            sql($sql);
            $order++;
        }
        $out = array("success" => true);
        break;
    case "update_scheme":
        $enable_interceptor = $db->escapeSimple(utf8_decode($_POST['enable_interceptor']));
        $enable_interceptor = (isset($enable_interceptor) && $enable_interceptor == 'Y') ? TRUE : FALSE;
	$scheme_name = $db->escapeSimple(preg_replace('/\s/i', '_', preg_replace('/\+/i', '_', trim($_POST['scheme_name']))));
	$scheme_name_orig = $db->escapeSimple($_POST['scheme_name_orig']);
	$DID = $db->escapeSimple($_POST['DID']);
	$CID_rules = $db->escapeSimple($_POST['CID_rules']);
	$Prefix_URL = $db->escapeSimple($_POST['Prefix_URL']);
	$Curl_Timeout = $db->escapeSimple($_POST['Curl_Timeout']);
	$http_password = $db->escapeSimple(utf8_decode($_POST['http_password']));
	$http_username = $db->escapeSimple(utf8_decode($_POST['http_username']));
	$SPAM_Text = $db->escapeSimple($_POST['SPAM_Text']);
	$SPAM_Text_Substitute = (isset($_POST['SPAM_Text_Substitute'])) ? $db->escapeSimple($_POST['SPAM_Text_Substitute']) : 'N';
	$processor =  $db->escapeSimple(utf8_decode($_POST['processor']));
	$multifecta_timeout =  $db->escapeSimple(utf8_decode($_POST['multifecta_timeout']));
	$SPAM_threshold = $db->escapeSimple($_POST['SPAM_threshold']);
	$error = false;

        $type = $db->escapeSimple($_POST['goto0']);
	$destination = $db->escapeSimple($_POST[$type.'0']);

	//see if the scheme name has changed, and make sure that there isn't already one named the new name.
	if($scheme_name == "")
	{
		$error = true;
		print '<p><strong>Scheme names cannot be blank.</strong></p>';
	}

	if(($scheme_name != $scheme_name_orig) && !$error)
	{
		$sql = "SELECT * FROM superfectaconfig WHERE source='base_".$scheme_name."'";
		$results = sql($sql, "getAll");

		if(!empty($results))
		{
			$error = true;
			print '<p><strong>You cannot rename a scheme the same thing as an existing scheme.</strong></p>';
		}
		else
		{
			$sql = "UPDATE superfectaconfig SET source = 'base_".$scheme_name."' WHERE source = 'base_".$scheme_name_orig."'";
			sql($sql);

			$sql = "UPDATE superfecta_to_incoming SET scheme = 'base_".$scheme_name."' WHERE scheme = 'base_".$scheme_name_orig."'";
			sql($sql);
		}
	}

	if(!$error)
	{
		//update database
                if($enable_interceptor) {
                	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','spam_interceptor','Y')";
                } else {
                	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','spam_interceptor','N')";
                }
                sql($sql);
            	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','spam_destination','$destination')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','Prefix_URL','$Prefix_URL')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','Curl_Timeout','$Curl_Timeout')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','processor','$processor')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','multifecta_timeout','$multifecta_timeout')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_Text','$SPAM_Text')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_Text_Substitute','$SPAM_Text_Substitute')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','DID','$DID')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','CID_rules','$CID_rules')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_threshold','$SPAM_threshold')";
		sql($sql);
		print '<p><strong>CID Scheme Updated</strong></p>';
	}

	//add ordering information to database if this scheme doesn't have it
	$highest_order = 0;
	$already_has_order = false;
	$sql = "SELECT source,ABS(value) FROM superfectaconfig WHERE field = 'order' ORDER BY ABS(value)";
	$results = sql($sql, "getAll");
	foreach($results as $val)
	{
		if($val[0] == "base_".$scheme_name)
		{
			$already_has_order = true;
			break;
		}
		$highest_order = $val[1];
	}

	if(!$already_has_order)
	{
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','order',".($highest_order+1).")";
		sql($sql);
	}
        break;
}

echo json_encode($out);
