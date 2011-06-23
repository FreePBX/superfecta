<?php
#############################################################################
# Written by Jeremy Jacobs
#	Fitness Plus Equipment Data Sources, Inc.
# http://www.FitnessRepairParts.com
#	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
#	the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
#############################################################################

require_once 'DB.php';
define("AMP_CONF", "/etc/amportal.conf");

$amp_conf = parse_amportal_conf(AMP_CONF);
if (count($amp_conf) == 0)
{
	fatal("FAILED");
}

function parse_amportal_conf($filename)
{
	$file = file($filename);
	foreach ($file as $line)
	{
		if (preg_match("/^\s*([a-zA-Z0-9_]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches))
		{
			$conf[ $matches[1] ] = $matches[2];
		}
	}
	return $conf;
}

$dsn = array(
		'phptype'  => 'mysql',
		'username' => $amp_conf['AMPDBUSER'],
		'password' => $amp_conf['AMPDBPASS'],
		'hostspec' => $amp_conf['AMPDBHOST'],
		'database' => $amp_conf['AMPENGINE'],
);
$options = array();
$db =& DB::connect($dsn, $options);
if (PEAR::isError($db))
{
	die($db->getMessage());
}

$selected_source = (isset($_REQUEST['selected_source'])) ? $_REQUEST['selected_source'] : '';
$src_up = '';
$src_down = '';
if(isset($_REQUEST['src_up']))
{
	$src_up = $_REQUEST['src_up'];
	$selected_source = (trim($src_up) != '') ? trim($src_up) : $selected_source;
}
if(isset($_REQUEST['src_down']))
{
	$src_down = $_REQUEST['src_down'];
	$selected_source = (trim($src_down) != '') ? trim($src_down) : $selected_source;
}
$source_param_form = (isset($_REQUEST['source_param_form'])) ? $_REQUEST['source_param_form'] : '';
$usage_mode = 'UI Display';
$first_run = (isset($_REQUEST['first_run'])) ? $_REQUEST['first_run'] : '';
$scheme = (isset($_REQUEST['scheme'])) ? $_REQUEST['scheme'] : '';
$check_updates = (isset($_REQUEST['check_updates'])) ? $_REQUEST['check_updates'] : '';
$update_file = (isset($_REQUEST['update_file'])) ? $_REQUEST['update_file'] : '';
$delete_file = (isset($_REQUEST['delete_file'])) ? $_REQUEST['delete_file'] : '';
$revert_file = (isset($_REQUEST['revert_file'])) ? $_REQUEST['revert_file'] : '';
$src_print = array();
$src_on = array();
$src_cnt = 1;
$src_files = array();
$update_site_unavailable = false;

// Load files available on live update
if(($check_updates == 'on') || ($update_file != ''))
{
	$update_array = array();
	$dst_offset = (60*60); // We need to offset by an hour to compensate for a possible DST.  This should be set to zero
			       // if we know the update server is giving us UTC.  When using PST, we dont know if the file was created
			       // In PST, or PDT -- so we subtract an hour or 2 to make sure.  Not ideal, but the MD5 check should
			       // Help avoid any unnecessary updates.		
	$updateserver_timezone = "PST"; // Would be nice if this could by UTC.  See above.

	// Temporary cookie file
	$temp_cookie_file = false;
	//$temp_cookie_file = tempnam("/tmp", "CURLCOOKIE");

	// Load the login page to get cookies set and to get authentication key
	$login_url = "http://projects.colsolgrp.net/login";
	$file_url = "http://projects.colsolgrp.net/projects/list_files/superfecta";
	//$update_content = get_url_contents($login_url,false,$file_url,$temp_cookie_file);
	//$pattern = "/<input name=\"authenticity_token\" type=\"hidden\" value=\"([^\"]+)\"/";
	//if(preg_match($pattern, $update_content, $match)){
		//$authenticity_token = $match[1];
		//$post_array = array(
		//	"authenticity_token" => $authenticity_token,
		//	"back_url" => urlencode($file_url),
		//	"username" => "superfectaupdates",
		//	"password" => "update_password"
		//);
		// Login
		//$update_content = get_url_contents($login_url,$post_array,$login_url,$temp_cookie_file);
		// Get the file list
		$update_content = get_url_contents($file_url,false,$login_url,$temp_cookie_file);
		$file_pattern = "/<td class=\"filename\"><a href=\"(\/attachments\/download\/[^\"]+)\" title=\"[^\"]*?\">([^<]+)\<\/a>/";
		$date_pattern = "/<td class=\"created_on\">([^<]+)<\/td>/";
		$md5_pattern = "/<td class=\"digest\">([^<]+)<\/td>/";
		if(preg_match_all($file_pattern, $update_content, $file_match) && preg_match_all($date_pattern, $update_content, $date_match) && preg_match_all($md5_pattern, $update_content, $md5_match)){
			foreach($file_match[1] as $key => $value){
				if((substr($file_match[2][$key],0,7)=='source-') && (substr($file_match[2][$key],strlen($file_match[2][$key])-4)=='.php')){
					$this_source_name = substr(substr(trim($file_match[2][$key]),7),0,-4);
					$update_array[$this_source_name]['link'] = "http://projects.colsolgrp.net".trim($file_match[1][$key]);
					$update_array[$this_source_name]['date'] = strtotime(trim($date_match[1][$key] . " " . $updateserver_timezone ));
					$update_array[$this_source_name]['md5'] = trim($md5_match[1][$key]);
				}
			}
		}else{
			//site un-available, give error.
			$update_site_unavailable = true;
			$check_updates = 'off';
		}
	//}else{
	//	//site un-available, give error.
	//	$update_site_unavailable = true;
	//	$check_updates = 'off';
	//}
	// Clean up the temp cookie file
	//@unlink($temp_cookie_file);
}

//process updates from online server first
if($update_file != '')
{
	$parsed_url = parse_url($update_file);
	$parsed_path = pathinfo($parsed_url['path']);
	$update_source_name = $this_source_name = substr(substr(trim($parsed_path['basename']),7),0,-4);
	$local_destination_file = "bin/".$parsed_path['basename'];

	//rename and keep old file if it exists
	if(is_file($local_destination_file))
	{
		rename($local_destination_file,"bin/old_".$parsed_path['basename']);
	}
	copy($update_file,$local_destination_file);
	if(cisf_md5_file($local_destination_file) != ($update_array[$update_source_name]['md5'])){
		echo "Warning: Downloaded file '" . $parsed_path['basename'] . "' did not pass MD5 verification.";
		if(is_file("bin/old_".$parsed_path['basename'])){
			echo " Reverting update.";
			$revert_file = $update_source_name;
		}else{
			echo " Update aborted.";
			$delete_file = $update_source_name;
		}
		echo "<br>\n<br>\n";
	}else{
		//echo "Updated $update_source_name successfully.<br>\n<br>\n";
		// Reset the date to the correct one
		touch($local_destination_file, $update_array[$update_source_name]['date']);
	}
}

//delete file if requested.
if($delete_file != '')
{
	//right now we're keeping and "old_" files just in case the user wants to revert back in the future
	if(is_file("bin/source-".$delete_file.".module"))
	{
		unlink("bin/source-".$delete_file.".module");
	}
}

//revert to old file if requested
if($revert_file != '')
{
	if(is_file("bin/old_source-".$revert_file.".module"))
	{
		if(is_file("bin/source-".$revert_file.".module"))
		{
			unlink("bin/source-".$revert_file.".module");
		}
		rename("bin/old_source-".$revert_file.".module","bin/source-".$revert_file.".module");
	}
}

//get a list of the files that are on this local server
foreach (glob("bin/source-*.module") as $filename)
{
	if($filename != '')
	{
		$source_desc = '';
		$source_param = array();
		require_once('bin/superfecta_base.php');
		require_once($filename);		
		$this_source_name = substr(substr($filename,11),0,-7);	
		$source_class = NEW $this_source_name;
		$settings = $source_class->settings();	
		$src_files[$this_source_name]['desc'] = $settings['desc'];		
		$src_files[$this_source_name]['param'] = $settings['param'];
		$source_param = $settings['source_param'];
		$src_files[$this_source_name]['param'] = $settings['source_param'];
						
		//update the database if this source was the last displayed form.
		if($source_param_form == $this_source_name)
		{
			foreach($source_param as $key=>$val)
			{
				$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('".substr($scheme,5).'_'.$this_source_name."','$key','".mysql_real_escape_string(utf8_decode($_REQUEST[$key]))."')";
				$db->query($sql);
			}
		}
	}
}

//go through previously enabled sources
$sql = "SELECT value FROM superfectaconfig WHERE source='$scheme' AND field='sources'";
$res = $db->getOne($sql);
$res_src = explode(',',$res);
foreach($res_src as $val)
{
	if(($val != '') && array_key_exists($val,$src_files))
	{
		eval('$this_val = (isset($_REQUEST["'.$val.'"])) ? $_REQUEST["'.$val.'"] : "";');
		if(($this_val == 1) || ($first_run == 1))
		{
			$this_cnt = $src_cnt;
			if($val == $src_up)
			{
				$this_cnt = $src_cnt - 3;
			}
			else if($val == $src_down)
			{
				$this_cnt = $src_cnt + 3;
			}

			$src_print[$this_cnt]['name'] = $val;
			$src_print[$this_cnt]['value'] = 1;
			$src_cnt = $src_cnt + 2;
			$src_on[] = $val;
		}
	}
}

$enabled_cnt = count($src_print);

//tack on the disabled sources at the end.
foreach($src_files as $key=>$val)
{
	if(!in_array($key,$src_on))
	{
		eval('$this_val = (isset($_REQUEST["'.$key.'"])) ? $_REQUEST["'.$key.'"] : "";');
		if($this_val == 1)
		{
			//this source just got enabled.
			$src_print[$src_cnt]['name'] = $key;
			$src_print[$src_cnt]['value'] = 1;
			$enabled_cnt++;
			$selected_source = $key;
		}
		else
		{
			$src_print[($src_cnt+200)]['name'] = $key;
			$src_print[($src_cnt+200)]['value'] = 0;
		}

		$src_cnt++;
	}
}

ksort($src_print);

print '<input type="hidden" name="src_up" value="">
		<input type="hidden" name="src_down" value="">
		<input type="hidden" name="selected_source" value="">
		<input type="hidden" name="update_file" value="">
		<input type="hidden" name="delete_file" value="">
		<input type="hidden" name="revert_file" value=""><font size=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="checkbox" name="check_updates" value="yes" ';
if($check_updates == 'on') { print ' checked'; }
print ' onClick="document.forms.CIDSources.submit();">&nbsp;Check for Data Source File updates online.<br></font>';
if($update_site_unavailable)
{
	//print a message displaying a site unavailable message.
	print '<span style="color:red;">The update site is currently unavailable.</span>';
}
print '<table border="0" id="table1" cellspacing="0" cellpadding="2">
		  <tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong>Data Source Name</strong></td>
				<td align="center"><strong>Disabled</strong></td>
				<td align="center"><strong>Enabled</strong></td>';
if(($selected_source != '') && !empty($src_files[$selected_source]['param']))
{
	print '<td rowspan="40" bgcolor="#E0E0E0" valign="top" align="center" width="350">
			<strong>'.str_replace('_',' ',$selected_source).' Options</strong><br>';
	if(empty($src_files[$selected_source]['param']))
	{
		print '<br><br>Nothing to configure';
	}
	else
	{
		$value_array = array();
		$sql = "SELECT field,value FROM superfectaconfig WHERE source='".substr($scheme,5).'_'.$selected_source."'";
		$res = $db->query($sql);
		while ($row = $res->fetchRow())
		{
			$value_array[$row[0]] = $row[1];
		}
		print '<input type="hidden" name="source_param_form" value="'.$selected_source.'">
				<table border="0">';
		foreach($src_files[$selected_source]['param'] as $key=>$val)
		{
			//set default value if one is specified and the parameter currently has no value.
			$value_array[$key] = (
						(
							!(
								(isset($value_array[$key])) 
								&& 
								(!empty($value_array[$key]))
							)
						) && 
						(
							(isset($val['default']))
							&&
							(!empty($val['default']))
						) 
					) 
				? $val['default'] 
				: $value_array[$key];

			print '<tr>
					<td valign="top" align="right"><a href="javascript: return false;" class="info">'.str_replace('_',' ',$key).':<span>'.$val['desc'].'</span></a></td>
					<td align="left">';
			if($val['type'] == 'select')
			{
				print '<select name="'.$key.'">';
				foreach($val['option'] as $key2=>$val2)
				{
					print '<option ';
					if($key2 == $value_array[$key]) { print 'selected="" '; }
					print ' value="'.$key2.'"';
					print '>'.$val2.'</option>';
				}
				print '</select>';
			}
			else if($val['type'] == 'number')
			{
				print '<input type="text" size="10" maxlength="10" name="'.$key.'" value="'.$value_array[$key].'">';
			}
			else if($val['type'] == 'textarea')
			{
				//print '<textarea rows="5" cols="25" name="'.$key.'">'.$value_array[$key].'</textarea>';
				print '<textarea rows="5" cols="25" name="'.$key.'">'.utf8_encode($value_array[$key]).'</textarea>';

			}
			else if($val['type'] == 'checkbox')
			{
				print '<input type="checkbox" name="'.$key.'"';
				if($value_array[$key] == 'on') { print ' checked'; }
				print '>';
			}
			else
			{
				//print '<input type="'.$val['type'].'" size="23" maxlength="255" name="'.$key.'" value="'.$value_array[$key].'">';
				print '<input type="'.$val['type'].'" size="23" maxlength="255" name="'.$key.'" value="'.utf8_encode($value_array[$key]).'">';

			}
			print '</td>
				</tr>';
		}
		print '</table><br><br>
				<input type="submit" value="Apply">';
	}
	print '</td>';
}
print '</tr>';
$comma = '';
$src_list = '';
$enabled_src_list = '';
$count = 0;
foreach($src_print as $val)
{
	$count++;
	$src_list .= $comma.$val['name'];
	if($val['value'] == 1)
	{
		$enabled_src_list .= $comma.$val['name'];
	}

	if($val['name'] == $selected_source)
	{
		print '<tr style="background-color:#E0E0E0;">';
	}
	else
	{
		print '<tr>';
	}
	print '<td>';
	if(($comma != '') && ($val['value'] == 1))
	{
		print '<a href="javascript:document.forms.CIDSources.src_up.value=\''.$val['name'].'\';document.forms.CIDSources.submit();"><img src="images/scrollup.gif" border="0" alt="Up Arrow" title="Move Up List"></a>';
	}
	else
	{
		print '&nbsp;';
	}
	print '</td>
	    <td>';
	if(($val['value'] == 1) && ($count < $enabled_cnt))
	{
		print '<a href="javascript:document.forms.CIDSources.src_down.value=\''.$val['name'].'\';document.forms.CIDSources.submit();"><img src="images/scrolldown.gif" border="0" alt="Down Arrow" title="Move Down List"></a>';
	}
	else
	{
		print '&nbsp;';
	}
	print '</td>
			<td>
				<a href="javascript:document.forms.CIDSources.delete_file.value=\''.$val['name'].'\';document.forms.CIDSources.submit();"><img src="modules/superfecta/delete.gif" border="0" alt="Delete Button" title="Delete This Source File"></a>
			</td>
			<td>';
	if(is_file("bin/old_source-".$val['name'].".php"))
	{
		print '<a href="javascript:document.forms.CIDSources.revert_file.value=\''.$val['name'].'\';document.forms.CIDSources.submit();"><img src="modules/superfecta/revert.gif" border="0" alt="Revert Button" title="Revert to previous version of this file."></a>';
	}
	else
	{
		print '&nbsp;';
	}
	print '</td>
			<td>
				<a href="javascript:document.forms.CIDSources.selected_source.value=\''.$val['name'].'\';document.forms.CIDSources.submit();" class="info">'.str_replace('_',' ',$val['name']).': ';
	if(!empty($src_files[$val['name']]['param']))
	{
		print ' ==>';
	}
	print '<span>'.$src_files[$val['name']]['desc'].'</span></a>';
	//check to see if there are updates.
	if($check_updates == 'on')
	{
		if(key_exists($val['name'],$update_array))
		{
			$this_last_update = filemtime("bin/source-".$val['name'].".php");
			$this_md5 = cisf_md5_file("bin/source-".$val['name'].".php");
			if(($update_array[$val['name']]['md5'] != $this_md5)  && (($update_array[$val['name']]['date']+$dst_offset) > $this_last_update))
			{
				print ' <a href="javascript:document.forms.CIDSources.update_file.value=\''.$update_array[$val['name']]['link'].'\';document.forms.CIDSources.submit();">update available</a>';
			}
			elseif(($update_array[$val['name']]['md5'] != $this_md5)  && (($update_array[$val['name']]['date']+$dst_offset) < $this_last_update))
			{
				print ' <a href="javascript:document.forms.CIDSources.update_file.value=\''.$update_array[$val['name']]['link'].'\';document.forms.CIDSources.submit();">downgrade available</a>';
			}
		}
		else
		{
			print ' unsupported module';
		}
	}
	print '</td>
	    <td align="center"><input type="radio" value="0" name="'.$val['name'].'"'.(($val['value'] == 0) ? ' checked' : '').' onclick="document.forms.CIDSources.submit();"></td>
	    <td align="center"><input type="radio" value="1" name="'.$val['name'].'"'.(($val['value'] == 1) ? ' checked' : '').' onclick="document.forms.CIDSources.submit();"></td>
	  </tr>';
	$comma = ',';
}

//create a list of source files that can still be added to the server.
if($check_updates == 'on')
{
	$options_list = '';
	$src_key_list = array();
	foreach($src_print as $val2){
		$src_key_list[$val2['name']] = 'installed';
	}
	foreach($update_array as $key=>$val)
	{
		if(!isset($src_key_list[$key]))
		{
			$options_list .= '<OPTION value="'.$val['link'].'">'.str_replace('_',' ',$key).'</OPTION>';
		}
	}

	if(!empty($options_list))
	{
		print '<tr>
			<td>
			<a href="javascript:document.forms.CIDSources.update_file.value=document.forms.CIDSources.add_source_file.value;document.forms.CIDSources.submit();"><img src="images/scrollup.gif" border="0" alt="Up Arrow" title="Install Source"></a>
			</td>
	    		<td>&nbsp;</td>
	    		<td>&nbsp;</td>
			<td>&nbsp;</td>
		    	<td colspan="3">
		    	<SELECT name="add_source_file">
				<OPTION value="">Select source to install</OPTION>'.$options_list.'
			</SELECT> <- More sources available
			</td>
		  </tr>';
	}
}
print '</table>
	<input type="hidden" name="src_list" value="'.$src_list.'">';

$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('$scheme','sources','$enabled_src_list')";
$db->query($sql);

function cisf_url_encode_array($arr){
	$string = "";
	foreach ($arr as $key => $value) {
		$string .= $key . "=" . urlencode($value) . "&";
	}
	trim($string,"&");
	return $string;
}

/**
Returns the content of a URL.
*/
function get_url_contents($url,$post_data=false,$referrer=false,$cookie_file=false,$useragent=false)
{
	$crl = curl_init();
	if(!$useragent){
		// Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)
		$useragent="Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	}
	if($referrer){
		curl_setopt ($crl, CURLOPT_REFERER, $referrer);
	}
	curl_setopt($crl,CURLOPT_USERAGENT,$useragent);
	curl_setopt($crl,CURLOPT_URL,$url);
	curl_setopt($crl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($crl,CURLOPT_FAILONERROR,true);
	if($cookie_file){
		curl_setopt($crl, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($crl, CURLOPT_COOKIEFILE, $cookie_file);
	}
	if($post_data){
		curl_setopt($crl, CURLOPT_POST, 1); // set POST method
		curl_setopt($crl, CURLOPT_POSTFIELDS, cisf_url_encode_array($post_data)); // add POST fields
	}

	$ret = trim(curl_exec($crl));
	if(curl_error($crl) && $debug)
	{
		print ' '.curl_error($crl).' ';
	}

	//if debug is turned on, return the error number if the page fails.
	if($ret === false)
	{
		$ret = '';
	}
	//something in curl is causing a return of "1" if the page being called is valid, but completely empty.
	//to get rid of this, I'm doing a nasty hack of just killing results of "1".
	if($ret == '1')
	{
		$ret = '';
	}
	curl_close($crl);
	return $ret;
}

// Abstract file get content to support older versions of php
function cisf_file_get_contents($file){

	if(function_exists('file_get_contents')){
		return file_get_contents($file);
	} else {
		$contents = '';
		$fp = @fopen($file,"rb");
		while (!feof($fp)) {
			$contents .= fread($fp, 16384);
		}
		fclose($fp);
		return $contents;
	}
}

// Abstract md5_file function for older versions of php
function cisf_md5_file($file, $raw = false){

	if(function_exists('md5_file')){
		return md5_file($file, $raw);
	} else {
		$contents = cisf_file_get_contents($file);
		return md5($contents, $raw);
	}
}

?>
