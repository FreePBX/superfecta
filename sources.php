<?php
#############################################################################
# Written by Jeremy Jacobs
#	Fitness Plus Equipment Data Sources, Inc.
# http://www.FitnessRepairParts.com
#	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
#	the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
#############################################################################

require("config.php");
define("UPDATE_SERVER", "https://raw.github.com/tm1000/Caller-ID-Superfecta/v2.2.5.x/bin/");

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

//process updates from online server first
if($update_file != '')
{
	$parsed_url = parse_url($update_file);
	$parsed_path = pathinfo($parsed_url['path']);

	//rename and keep old file if it exists
	if(is_file("bin/".$parsed_path['basename']))
	{
		rename("bin/".$parsed_path['basename'],"bin/old_".$parsed_path['basename']);
	}
	copy($update_file,"bin/".$parsed_path['basename']);
}

//delete file if requested.
if($delete_file != '')
{
	//right now we're keeping and "old_" files just in case the user wants to revert back in the future
	if(is_file("bin/source-".$delete_file.".php"))
	{
		unlink("bin/source-".$delete_file.".php");
	}
}

//revert to old file if requested
if($revert_file != '')
{
	if(is_file("bin/old_source-".$revert_file.".php"))
	{
		if(is_file("bin/source-".$revert_file.".php"))
		{
			unlink("bin/source-".$revert_file.".php");
		}
		rename("bin/old_source-".$revert_file.".php","bin/source-".$revert_file.".php");
	}
}

//get a list of the files that are on this local server
foreach (glob("bin/source-*.php") as $filename)
{
	if($filename != '')
	{
		$source_desc = '';
		$source_param = array();
		include($filename);
		$this_source_name = substr(substr($filename,11),0,-4);
		$src_files[$this_source_name]['desc'] = $source_desc;
		$src_files[$this_source_name]['param'] = $source_param;

		//update the database if this source was the last displayed form.
		if($source_param_form == $this_source_name)
		{
			foreach($source_param as $key=>$val)
			{
				//$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('".substr($scheme,5).'_'.$this_source_name."','$key','".mysql_escape_string($_REQUEST[$key])."')";
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

if($check_updates == 'on')
{
	$update_array = array();
	// Load files available on live update
	if(($check_updates == 'on') || ($update_file != ''))
	{
		$update_array = array();
		$source_list = superfecta_xml2array2(UPDATE_SERVER.'source-list.xml');
		
		foreach($source_list['data']['source'] as $sources) {
			
			$this_source_name = basename(trim($sources['name']));
			$this_source_name = pathinfo($this_source_name);
			$this_source_name2 = str_replace(".".$this_source_name['extension'],"",$this_source_name['basename']);
			$this_source_name2 = str_replace("source-", "", $this_source_name2);
			
			$update_array[$this_source_name2]['link'] = UPDATE_SERVER.$sources['name'];
			$update_array[$this_source_name2]['date'] = $sources['modified'];
			$update_array[$this_source_name2]['md5'] = $sources['md5'];
		}
		/*
		$update_site_unavailable = true;
		$check_updates = 'off';
		*/
	}	
}

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
			if($update_array[$val['name']]['date'] > $this_last_update)
			{
				print ' <a href="javascript:document.forms.CIDSources.update_file.value=\''.$update_array[$val['name']]['link'].'\';document.forms.CIDSources.submit();">update available</a>';
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
	foreach($update_array as $key=>$val)
	{
		$in_array = false;
		foreach($src_print as $val2)
		{
			if($val2['name'] == $key)
			{
				$in_array = true;
				break;
			}
		}
		if(!$in_array)
		{
			$options_list .= '<OPTION value="'.$val['link'].'">'.str_replace('_',' ',$key).'</OPTION>';
		}
	}

	if(!empty($options_list))
	{
		print '<tr>
				<td>
					<a href="javascript:document.forms.CIDSources.update_file.value=document.forms.CIDSources.add_source_file.value;document.forms.CIDSources.submit();"><img src="images/scrollup.gif" border="0" alt="Up Arrow" title="Move Up List"></a>
				</td>
	    	<td>&nbsp;</td>
	    	<td>&nbsp;</td>
				<td>&nbsp;</td>
		    <td>
		    	<SELECT name="add_source_file">
						<OPTION value="">Select One</OPTION>'.$options_list.'
					</SELECT>
				</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		  </tr>';
	}
}
print '</table>
	<input type="hidden" name="src_list" value="'.$src_list.'">';

$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('$scheme','sources','$enabled_src_list')";
$db->query($sql);

/**
Returns the content of a URL.
*/
function get_url_contents($url)
{
	$crl = curl_init();
	$useragent="Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	curl_setopt($crl,CURLOPT_USERAGENT,$useragent);
	curl_setopt($crl,CURLOPT_URL,$url);
	curl_setopt($crl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($crl,CURLOPT_CONNECTTIMEOUT,5);
	curl_setopt($crl,CURLOPT_FAILONERROR,true);
	curl_setopt($crl,CURLOPT_TIMEOUT,5);
	$ret = trim(curl_exec($crl));

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

function html2text($badStr)
{
	//remove PHP if it exists
	while(substr_count( $badStr, '<'.'?' ) && substr_count( $badStr, '?'.'>' ) && strpos( $badStr, '?'.'>', strpos( $badStr, '<'.'?' ) ) > strpos( $badStr, '<'.'?' ))
	{
		$badStr = substr( $badStr, 0, strpos( $badStr, '<'.'?' ) ) . substr( $badStr, strpos( $badStr, '?'.'>', strpos( $badStr, '<'.'?' ) ) + 2 );
	}

	//remove comments
	while( substr_count( $badStr, '<!--' ) && substr_count( $badStr, '-->' ) && strpos( $badStr, '-->', strpos( $badStr, '<!--' ) ) > strpos( $badStr, '<!--' ) )
	{
		$badStr = substr( $badStr, 0, strpos( $badStr, '<!--' ) ) . substr( $badStr, strpos( $badStr, '-->', strpos( $badStr, '<!--' ) ) + 3 );
	}

	//now make sure all HTML tags are correctly written (> not in between quotes)
	for( $x = 0, $goodStr = '', $is_open_tb = false, $is_open_sq = false, $is_open_dq = false; isset($badStr{$x}) && strlen( $chr = $badStr{$x} ); $x++ )
	{
		//take each letter in turn and check if that character is permitted there
		switch($chr)
		{
			case '<':
			    if( !$is_open_tb && strtolower( substr( $badStr, $x + 1, 5 ) ) == 'style' )
					{
			        $badStr = substr( $badStr, 0, $x ) . substr( $badStr, strpos( strtolower( $badStr ), '</style>', $x ) + 7 ); $chr = '';
			    }
					elseif( !$is_open_tb && strtolower( substr( $badStr, $x + 1, 6 ) ) == 'script' )
					{
			        $badStr = substr( $badStr, 0, $x ) . substr( $badStr, strpos( strtolower( $badStr ), '</script>', $x ) + 8 ); $chr = '';
			    }
					elseif( !$is_open_tb )
					{
						$is_open_tb = true;
					}
					else
					{
						$chr = '&lt;';
					}
			    break;
			case '>':
			    if( !$is_open_tb || $is_open_dq || $is_open_sq )
					{
						$chr = '&gt;';
					}
					else
					{
						$is_open_tb = false;
					}
			    break;
			case '"':
			    if( $is_open_tb && !$is_open_dq && !$is_open_sq )
					{
						$is_open_dq = true;
					}
			    elseif( $is_open_tb && $is_open_dq && !$is_open_sq )
					{
						$is_open_dq = false;
					}
			    else
					{
						$chr = '&quot;';
					}
			    break;
			case "'":
			    if( $is_open_tb && !$is_open_dq && !$is_open_sq )
					{
						$is_open_sq = true;
					}
			    elseif( $is_open_tb && !$is_open_dq && $is_open_sq )
					{
						$is_open_sq = false;
					}
		}
		$goodStr .= $chr;
	}

	//now that the page is valid (I hope) for strip_tags, strip all unwanted tags
	$goodStr = strip_tags( $goodStr, '<title><hr><h1><h2><h3><h4><h5><h6><div><p><pre><sup><ul><ol><br><dl><dt><table><caption><tr><li><dd><th><td><a><area><img><form><input><textarea><button><select><option>' );

	//strip extra whitespace except between <pre> and <textarea> tags
	$badStr = preg_split( "/<\/?pre[^>]*>/i", $goodStr );
	for( $x = 0; isset( $badStr[$x] ) && is_string( $badStr[$x] ); $x++ )
	{
		if( $x % 2 )
		{
			$badStr[$x] = '<pre>'.$badStr[$x].'</pre>';
		}
		else
		{
			$goodStr = preg_split( "/<\/?textarea[^>]*>/i", $badStr[$x] );
			for( $z = 0; isset( $goodStr[$z] ) && is_string( $goodStr[$z] ); $z++ )
			{
				if($z % 2)
				{
					$goodStr[$z] = '<textarea>'.$goodStr[$z].'</textarea>';
				}
				else
				{
			  	$goodStr[$z] = preg_replace( "/\s+/", ' ', $goodStr[$z] );
				}
			}
			$badStr[$x] = implode('',$goodStr);
		}
	}
	$goodStr = implode('',$badStr);
	//remove all options from select inputs
	$goodStr = preg_replace( "/<option[^>]*>[^<]*/i", '', $goodStr );
	//replace all tags with their text equivalents
	$goodStr = preg_replace( "/<(\/title|hr)[^>]*>/i", "\n          --------------------\n", $goodStr );
	$goodStr = preg_replace( "/<(h|div|p)[^>]*>/i", "\n\n", $goodStr );
	$goodStr = preg_replace( "/<sup[^>]*>/i", '^', $goodStr );
	$goodStr = preg_replace( "/<(ul|ol|br|dl|dt|table|caption|\/textarea|tr[^>]*>\s*<(td|th))[^>]*>/i", "\n", $goodStr );
	$goodStr = preg_replace( "/<li[^>]*>/i", "\n· ", $goodStr );
	$goodStr = preg_replace( "/<dd[^>]*>/i", "\n\t", $goodStr );
	$goodStr = preg_replace( "/<(th|td)[^>]*>/i", "\t", $goodStr );
	$goodStr = preg_replace( "/<a[^>]* href=(\"((?!\"|#|javascript:)[^\"#]*)(\"|#)|'((?!'|#|javascript:)[^'#]*)('|#)|((?!'|\"|>|#|javascript:)[^#\"'> ]*))[^>]*>/i", "[LINK: $2$4$6] ", $goodStr );
	$goodStr = preg_replace( "/<img[^>]* alt=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "[IMAGE: $2$3$4] ", $goodStr );
	$goodStr = preg_replace( "/<form[^>]* action=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "\n[FORM: $2$3$4] ", $goodStr );
	$goodStr = preg_replace( "/<(input|textarea|button|select)[^>]*>/i", "[INPUT] ", $goodStr );
	//strip all remaining tags (mostly closing tags)
	$goodStr = strip_tags( $goodStr );
	//convert HTML entities
	$goodStr = strtr( $goodStr, array_flip( get_html_translation_table( HTML_ENTITIES ) ) );
	preg_replace( "/&#(\d+);/me", "chr('$1')", $goodStr );
	//wordwrap
	$goodStr = wordwrap( $goodStr );
	//make sure there are no more than 3 linebreaks in a row and trim whitespace
	return preg_replace( "/^\n*|\n*$/", '', preg_replace( "/[ \t]+(\n|$)/", "$1", preg_replace( "/\n(\s*\n){2}/", "\n\n\n", preg_replace( "/\r\n?|\f/", "\n", str_replace( chr(160), ' ', $goodStr ) ) ) ) );
}

/**
Parse XML file into an array
*/
function superfecta_xml2array2($url, $get_attributes = 1, $priority = 'tag')
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
