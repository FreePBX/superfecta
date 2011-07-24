<?php
#############################################################################
# Written by Jeremy Jacobs
#	Fitness Plus Equipment Data Sources, Inc.
# http://www.FitnessRepairParts.com
#	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
#	the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
#############################################################################
define("UPDATE_SERVER", "https://raw.github.com/tm1000/Caller-ID-Superfecta/v3.x/bin/");
require("config.php");
require("bin/superfecta_base.php");
$superfecta = new superfecta_base;


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

if($check_updates == 'on')
{
	$update_array = array();
	// Load files available on live update
	if(($check_updates == 'on') || ($update_file != ''))
	{
		$update_array = array();
		$source_list = $superfecta->xml2array(UPDATE_SERVER.'source-list.xml');
		foreach($source_list['data']['source'] as $sources) {
			$this_source_name = substr(substr(trim($sources['name']),7),0,-7);
			$update_array[$this_source_name]['link'] = UPDATE_SERVER.$sources['name'];
			$update_array[$this_source_name]['date'] = $sources['modified'];
			$update_array[$this_source_name]['md5'] = $sources['md5'];
			$update_array[$this_source_name]['version_requirement'] = $sources['version_requirement'];
		}
		
		//Download a new version of superfecta_base
		$superfecta_base_data = $superfecta->get_url_contents(UPDATE_SERVER.'superfecta_base.php');
			
		//rename and keep old file if it exists
		echo "on";
		if(is_file("bin/superfecta_base.php"))
		{
			rename("bin/superfecta_base.php","bin/old_superfecta_base.php");
			unlink("bin/superfecta_base.php");
		}
		file_put_contents("bin/superfecta_base.php",$superfecta_base_data);
		unlink("bin/old_superfecta_base.php");
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
	if(is_file("bin/old_source-".$val['name'].".module"))
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
			$this_last_update = filemtime("bin/source-".$val['name'].".module");
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