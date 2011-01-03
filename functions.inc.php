<?php

function superfecta_hook_core($viewing_itemid, $target_menuid) {
	$html = '';
	if ($target_menuid == 'did')	{
		$html = '<tr><td colspan="2"><h5>';
		$html .= _("Superfecta CID Lookup");
		$html .= '<hr></h5></td></tr>';
		$html .= '<tr>';
		$html .= '<td colspan="2">';
		if(superfecta_did_get($viewing_itemid)){
			$checked_status = 'checked';
		}else{
			$checked_status = '';	
		}
		$html .= '<input type="checkbox" name="enable_superfecta" value="yes" '.$checked_status.'><a href="#" class="info">Enable CID Superfecta for this DID<span>'._("Sources can be added/removed in CID Superfecta section").'.</span></a>';
		$html .= '</td></tr>';
	}

	return $html;
	
}

function superfecta_hookProcess_core($viewing_itemid, $request) {
	
	// TODO: move sql to functions superfecta_did_(add, del, edit)
	if (!isset($request['action']))
		return;
	switch ($request['action'])	{
		case 'addIncoming':
			if($request['enable_superfecta'] == 'yes'){
				$sql = "INSERT INTO superfecta_to_incoming (extension, cidnum) values (".q($request['extension']).",".q($request['cidnum']).")";
				$result = sql($sql);
			}
		break;
		case 'delIncoming':
			$extarray = explode('/', $request['extdisplay'], 2);
			if(count($extarray) == 2){
					$sql = "DELETE FROM superfecta_to_incoming WHERE extension = ".q($extarray[0])." AND cidnum = ".q($extarray[1]);
					$result = sql($sql);
			}
		break;
		case 'edtIncoming':	// deleting and adding as in core module
			$extarray = explode('/', $request['extdisplay'], 2);
			if(count($extarray) == 2){
					$sql = "DELETE FROM superfecta_to_incoming WHERE extension = ".q($extarray[0])." AND cidnum = ".q($extarray[1]);
					$result = sql($sql);
			}
			if($request['enable_superfecta'] == 'yes'){
				$sql = "INSERT INTO superfecta_to_incoming (extension, cidnum) values (".q($request['extension']).",".q($request['cidnum']).")";
				$result = sql($sql);
			}
		break;
	}
}


function superfecta_hookGet_config($engine) {
	// TODO: integrating with direct extension <-> DID association
	// TODO: add option to avoid callerid lookup if the telco already supply a callerid name (GosubIf)
	global $ext;  // is this the best way to pass this?

	switch($engine) {
		case "asterisk":
			$pairing = superfecta_did_list();
			if(is_array($pairing)) {
				foreach($pairing as $item) {
					if ($item['superfecta_to_incoming_id'] != 0) {

						// Code from modules/core/functions.inc.php core_get_config inbound routes
						$exten = trim($item['extension']);
						$cidnum = trim($item['cidnum']);
						
						if ($cidnum != '' && $exten == '') {
							$exten = 's';
							$pricid = ($item['pricid']) ? true:false;
						} else if (($cidnum != '' && $exten != '') || ($cidnum == '' && $exten == '')) {
							$pricid = true;
						} else {
							$pricid = false;
						}
						$context = ($pricid) ? "ext-did-0001":"ext-did-0002";

						$exten = (empty($exten)?"s":$exten);
						$exten = $exten.(empty($cidnum)?"":"/".$cidnum); //if a CID num is defined, add it

						$ext->splice($context, $exten, 1, new ext_setvar('CALLERID(name)', '${lookupcid}'));
						$ext->splice($context, $exten, 1, new ext_agi(dirname(__FILE__).'/superfecta.agi'));
											
					}
				}
			}
		break;
	}

}

function superfecta_did_get($did){
	$extarray = explode('/', $did, 2);
	if(count($extarray) == 2){
		$sql = "SELECT * FROM superfecta_to_incoming WHERE extension = ".q($extarray[0])." AND cidnum = ".q($extarray[1]);
		$result = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
		if(is_array($result) && count($result)){
			return true;
		}
	}
	return false;	
}

function superfecta_did_list($id=false) {
	$sql = "
	SELECT superfecta_to_incoming_id, a.extension extension, a.cidnum cidnum, pricid FROM superfecta_to_incoming a 
	INNER JOIN incoming b
	ON a.extension = b.extension AND a.cidnum = b.cidnum
	";
	if ($id !== false && ctype_digit($id)) {
		$sql .= " WHERE superfecta_to_incoming_id = '".q($id)."'";
	}

	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	return is_array($results)?$results:array();
}

function setConfig()
{
	//clean up
	$scheme_name = mysql_real_escape_string($_POST['scheme_name']);
	$scheme_name_orig = mysql_real_escape_string($_POST['scheme_name_orig']);
	$DID = mysql_real_escape_string($_POST['DID']);
	$CID_rules = mysql_real_escape_string($_POST['CID_rules']);
	$Prefix_URL = mysql_real_escape_string($_POST['Prefix_URL']);
	$Curl_Timeout = mysql_real_escape_string($_POST['Curl_Timeout']);
	$http_password = mysql_real_escape_string(utf8_decode($_POST['http_password']));
	$http_username = mysql_real_escape_string(utf8_decode($_POST['http_username']));
	$SPAM_Text = mysql_real_escape_string($_POST['SPAM_Text']);
	$SPAM_Text_Substitute = (isset($_POST['SPAM_Text_Substitute'])) ? mysql_real_escape_string($_POST['SPAM_Text_Substitute']) : 'N';
	$error = false;

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
		}
	}

	if(!$error)
	{
		//update database
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','Prefix_URL','$Prefix_URL')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','Curl_Timeout','$Curl_Timeout')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_Text','$SPAM_Text')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_Text_Substitute','$SPAM_Text_Substitute')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','DID','$DID')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','CID_rules','$CID_rules')";
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

}

function getConfig($scheme)
{
	$return = array();
	$sql = "SELECT * FROM superfectaconfig WHERE source='$scheme'";
	$results = sql($sql, "getAll");
	foreach($results as $val)
	{
		$return[$val[1]] = $val[2];
	}

	//set some default values for creating a new scheme
	if($scheme == 'new')
	{
		$return['Curl_Timeout'] = 3;
		$return['SPAM_Text'] = 'SPAM';
	}

	return $return;
}

/**
Parse XML file into an array
*/
function xml2array($url, $get_attributes = 1, $priority = 'tag')
{
	$contents = "";
	if (!function_exists('xml_parser_create'))
	{
		return array ();
	}
	$parser = xml_parser_create('');
	if(!($fp = @ fopen($url, 'rb')))
	{
		return array ();
	}
	while(!feof($fp))
	{
		$contents .= fread($fp, 8192);
	}
	fclose($fp);
	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, trim($contents), $xml_values);
	xml_parser_free($parser);
	if(!$xml_values)
	{
		return; //Hmm...
	}
	$xml_array = array ();
	$parents = array ();
	$opened_tags = array ();
	$arr = array ();
	$current = & $xml_array;
	$repeated_tag_index = array ();
	foreach ($xml_values as $data)
	{
		unset ($attributes, $value);
		extract($data);
		$result = array ();
		$attributes_data = array ();
		if (isset ($value))
		{
			if($priority == 'tag')
			{
				$result = $value;
			}
			else
			{
				$result['value'] = $value;
			}
		}
		if(isset($attributes) and $get_attributes)
		{
			foreach($attributes as $attr => $val)
			{
				if($priority == 'tag')
				{
					$attributes_data[$attr] = $val;
				}
				else
				{
					$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}
		}
		if ($type == "open")
		{
			$parent[$level -1] = & $current;
			if(!is_array($current) or (!in_array($tag, array_keys($current))))
			{
				$current[$tag] = $result;
				if($attributes_data)
				{
					$current[$tag . '_attr'] = $attributes_data;
				}
				$repeated_tag_index[$tag . '_' . $level] = 1;
				$current = & $current[$tag];
			}
			else
			{
				if (isset ($current[$tag][0]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array($current[$tag],$result);
					$repeated_tag_index[$tag . '_' . $level] = 2;
					if(isset($current[$tag . '_attr']))
					{
						$current[$tag]['0_attr'] = $current[$tag . '_attr'];
						unset ($current[$tag . '_attr']);
					}
				}
				$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
				$current = & $current[$tag][$last_item_index];
			}
		}
		else if($type == "complete")
		{
			if(!isset ($current[$tag]))
			{
				$current[$tag] = $result;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				if($priority == 'tag' and $attributes_data)
				{
					$current[$tag . '_attr'] = $attributes_data;
				}
			}
			else
			{
				if (isset ($current[$tag][0]) and is_array($current[$tag]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					if ($priority == 'tag' and $get_attributes and $attributes_data)
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array($current[$tag],$result);
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $get_attributes)
					{
						if (isset ($current[$tag . '_attr']))
						{
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
						if ($attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
				}
			}
		}
		else if($type == 'close')
		{
			$current = & $parent[$level -1];
		}
	}
	return ($xml_array);
}
?>
