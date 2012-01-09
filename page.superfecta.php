<?php
//	Superfecta code maintained by forummembers at PBXIAF.
//  Development SVN is at projects.colsolgrp.net
//	Caller ID Tricfecta / Superfecta was invented by Ward Mundy,
//  based on another authors work.
//
//	v 1.0.0 - 1.1.0 Created / coded by Tony Shiffer
//	V 2.0.0 - 2.20 Principle developer Jeremy Jacobs
//  v 2.2.1		Significant development by Patrick ELX
//
//	This program is free software; you can redistribute it and/or modify it
//	under the terms of the GNU General Public License as published by
//	the Free Software Foundation; either version 2 of the License, or
//	(at your option) any later version.
//
require("includes/superfecta_base.php");
$superfecta = new superfecta_base;

$scheme = (isset($_REQUEST['scheme'])) ? $_REQUEST['scheme'] : '';
$module_info = $superfecta->xml2array("modules/superfecta/module.xml");

if(count($_POST))
{
	superfecta_setConfig();
	$scheme = ($_POST['scheme_name'] == $_POST['scheme_name_orig']) ? $_POST['scheme_name_orig'] : $_POST['scheme_name'];
	$scheme = "base_".$scheme;

	//Now save the $destination into the database
	//Return the $destination in the superfecta.agi script and use it such as this: $agi->exec_goto($destination) EG: $agi->exec_goto(context,extension,priority)
}

$goto = NULL;

$schemeup = (isset($_REQUEST['schemeup'])) ? $_REQUEST['schemeup'] : '';
$schemedown = (isset($_REQUEST['schemedown'])) ? $_REQUEST['schemedown'] : '';
$schemedelete = (isset($_REQUEST['schemedelete'])) ? $_REQUEST['schemedelete'] : '';
$schemecopy = (isset($_REQUEST['schemecopy'])) ? $_REQUEST['schemecopy'] : '';
$schemeonoff = (isset($_REQUEST['schemeonoff'])) ? $_REQUEST['schemeonoff'] : '';

//change the order of the list if requested.
if($schemeup != "")
{
	$sql = "SELECT ABS(value) FROM superfectaconfig WHERE source = '$schemeup' AND field = 'order'";
	$results= sql($sql, "getAll");
	//update positive numbers
	$sql = "UPDATE superfectaconfig SET value = ".$results[0][0]." WHERE field = 'order' AND value = ".($results[0][0] - 1);
	sql($sql);
	$sql = "UPDATE superfectaconfig SET value = (value - 1) WHERE field = 'order' AND value > 0 AND source = '$schemeup'";
	sql($sql);
	//update negative numbers
	$sql = "UPDATE superfectaconfig SET value = -".$results[0][0]." WHERE field = 'order' AND value = -".($results[0][0] - 1);
	sql($sql);
	$sql = "UPDATE superfectaconfig SET value = (value + 1) WHERE field = 'order' AND value < 0 AND source = '$schemeup'";
	sql($sql);
}
if($schemedown != "")
{
	$sql = "SELECT ABS(value) FROM superfectaconfig WHERE source = '$schemedown' AND field = 'order'";
	$results= sql($sql, "getAll");
	//update positive numbers
	$sql = "UPDATE superfectaconfig SET value = ".$results[0][0]." WHERE field = 'order' AND value = ".($results[0][0] + 1);
	sql($sql);
	$sql = "UPDATE superfectaconfig SET value = (value + 1) WHERE field = 'order' AND value > 0 AND source = '$schemedown'";
	sql($sql);
	//update negative numbers
	$sql = "UPDATE superfectaconfig SET value = -".$results[0][0]." WHERE field = 'order' AND value = -".($results[0][0] + 1);
	sql($sql);
	$sql = "UPDATE superfectaconfig SET value = (value - 1) WHERE field = 'order' AND value < 0 AND source = '$schemedown'";
	sql($sql);
}

//delete scheme if requested.
if($schemedelete != "")
{
	$sql = "SELECT ABS(value) FROM superfectaconfig WHERE source = '$schemedelete' AND field = 'order'";
	$results= sql($sql, "getAll");
	$sql = "UPDATE superfectaconfig SET value = (value - 1) WHERE field = 'order' AND value > ".$results[0][0];
	sql($sql);
	$sql = "UPDATE superfectaconfig SET value = (value + 1) WHERE field = 'order' AND value < -".$results[0][0];
	sql($sql);
	$sql = "DELETE FROM superfectaconfig WHERE source = '$schemedelete'";
	sql($sql);
}

//turn scheme on or off.
if($schemeonoff != "")
{
	$sql = "UPDATE superfectaconfig SET value = (value * -1) WHERE field = 'order' AND source = '$schemeonoff'";
	sql($sql);
}

//create a copy of a scheme if requested
if($schemecopy != "")
{
	//determine the highest order amount.
	$query = "SELECT MAX(ABS(value)) FROM superfectaconfig WHERE field = 'order'";
	$results= sql($query, "getAll");
	$new_order = $results[0][0] + 1;

	//set new scheme name
	$name_good = false;
	$new_name = $schemecopy.' copy';
	$new_name_count = 2;
	while(!$name_good)
	{
		$query = "SELECT * FROM superfectaconfig WHERE source = '".$new_name."'";
		$results= sql($query, "getAll");
		if(empty($results[0][0]))
		{
			$name_good = true;
		}
		else
		{
			if(substr($new_name,-4) == 'copy')
			{
				$new_name .= ' '.$new_name_count;
			}
			else
			{
				$new_name = substr($new_name,0,-2).' '.$new_name_count;
			}
			$new_name_count++;
		}
	}

	//copy data from existing scheme into new scheme
	$query = "SELECT field,value FROM superfectaconfig WHERE source = '".$schemecopy."'";
	$results= sql($query, "getAll");
	foreach($results as $val)
	{
		if(!empty($val))
		{
			if($val[0] == 'order')
			{
				$val[1] = $new_order;
			}
			$query = "REPLACE INTO superfectaconfig (source,field,value) VALUES('".$new_name."','$val[0]','$val[1]')";
			sql($query);
		}
	}

	$query = "SELECT source,field,value FROM superfectaconfig WHERE source LIKE '".substr($schemecopy,5)."\\_%'";
	$results= sql($query, "getAll");
	foreach($results as $val)
	{
		if(!empty($val))
		{
			$new_name_source = substr($new_name,5).substr($val[0],strlen(substr($schemecopy,5)));
			$query = "REPLACE INTO superfectaconfig (source,field,value) VALUES('$new_name_source','$val[1]','$val[2]')";
			sql($query);
		}
	}

	$scheme = $new_name;
}

$sql = "SELECT source, value FROM superfectaconfig WHERE source LIKE 'base_%' AND field = 'order' ORDER BY ABS(value)";
$results= sql($sql, "getAll");
print '<div class="rnav" style="width:250px;">
		<ul>
			<li><a href="config.php?display=superfecta&scheme=new">Add Caller ID Scheme</a></li>';
$count = 1;
foreach($results as $val)
{
	print '<li><img style="float: left; margin: 4px 1px 0px 1px;" class="button" onmouseover="this.style.cursor=\'pointer\';" onclick="window.location.href=\'config.php?display=superfecta&amp;schemeonoff='.$val[0].'\'" src="modules/superfecta/on_off.gif" alt="On/Off" title="Turn this scheme on and off">';
	if($count < count($results))
	{
		print '<img style="float: left; margin: 4px 1px 0px 1px;" class="button" onmouseover="this.style.cursor=\'pointer\';" onclick="window.location.href=\'config.php?display=superfecta&amp;schemedown='.$val[0].'\'" src="images/scrolldown.gif" alt="Down Arrow" title="Move Down List">';
	}
	else
	{
		print '<div style="width: 11px; float: left; height: 5px;"></div>';
	}
	if(($count > 1) && (count($results) > 1))
	{
		print '<img style="float: left; margin: 4px 1px 0px 1px;" class="button" onmouseover="this.style.cursor=\'pointer\';" onclick="window.location.href=\'config.php?display=superfecta&amp;schemeup='.$val[0].'\'" src="images/scrollup.gif" alt="Up Arrow" title="Move Up List">';
	}
	else
	{
		print '<div style="width: 11px; float: left; height: 5px;"></div>';
	}
	print '<img style="float: left; margin: 4px 1px 0px 1px;" class="button" onmouseover="this.style.cursor=\'pointer\';" onclick="window.location.href=\'config.php?display=superfecta&amp;schemecopy='.$val[0].'\'" src="modules/superfecta/copy.gif" alt="Duplicate Scheme" title="Duplicate Scheme">
			<img style="float: left; margin: 4px 1px 0px 1px;" class="button" onmouseover="this.style.cursor=\'pointer\';" onclick="decision(\'Are you sure you want to delete this Scheme?\',\'config.php?display=superfecta&amp;schemedelete='.$val[0].'\');" src="modules/superfecta/delete.gif" alt="Delete Button" title="Delete Scheme">
			<a href="config.php?display=superfecta&amp;scheme='.$val[0].'" style="float: left;">';
	if($val[1] > 0)
	{
		print substr($val[0],5);
	}
	else
	{
		print '<font color="#ff3c3c">'.substr($val[0],5).'</font>';
	}
	print '</a>&nbsp;</li>';
	$count++;
}
print '</ul>
		</div>
		<h1><font face="Arial">Caller ID Superfecta</font></h1>
		<hr>
		<p>CallerID Superfecta for FreePBX is a utility program which adds incoming CallerID name lookups to your Asterisk system using multiple data sources.<br><br> Add, Remove, Enable, Disable, Sort and Configure data sources as appropriate for your situation.</p>';

if($scheme != "")
{
	$conf = superfecta_getConfig($scheme);

        $goto = (!empty($conf['spam_destination'])) ? $conf['spam_destination'] : '';
        $spam_int = (!empty($conf['spam_interceptor']) && ($conf['spam_interceptor'] == 'Y')) ? 'checked' : '';

        //Get list of processors
        $list = array();
        $conf['processor'] = ((!isset($conf['processor'])) OR (empty($conf['processor']))) ? 'superfecta_single.php' : $conf['processor'];
        $processors_loc = dirname(__FILE__);
        foreach (glob($processors_loc."/includes/processors/*.php") as $filename) {
            $name = explode("_",basename($filename));
            $selected = ($conf['processor'] == basename($filename)) ? 'selected' : '';
            $list[] = "<option value='".basename($filename)."' $selected>".str_replace(".php","",$name[1])."</option>";
        }

	if (isset($conf['DID']) && (strlen(trim($conf['DID'])))){
		$did_test_html = '<a href="javascript:return(false);" class="info">DID Number:<span>DID number to test this scheme against</span></a> <input type="text" size="15" maxlength="20" name="testdid"><br>';
		$did_test_script = 'document.forms.debug_form.testdid.value,';
	} else {
		$did_test_html = '';
		$did_test_script = "'',";
	}
//get a list of the files that are on this local server
        $groups_list = array();
        
        require_once(dirname(__FILE__)."/includes/superfecta_base.php");
        foreach (glob(dirname(__FILE__)."/sources/source-*.module") as $filename)
        {
                if($filename != '')
                {
                        $source_desc = '';
                        $source_param = array();

                        require_once($filename);

                        $this_source_name = str_replace(".module","",str_replace(dirname(__FILE__)."/sources/source-","",$filename));
                        $source_class = NEW $this_source_name;		

                        $settings = $source_class->settings();
                        $groups = isset($settings['groups']) ? $settings['groups'] : NULL;

                        $glist = explode(',',$groups);
                        $groups_list['ALL'][] = $this_source_name;
                        foreach ($glist as $data) {
                            if(!empty($data)) {
                                $data = strtoupper($data);
                                $groups_list[$data][] = $this_source_name;
                            }
                        }
                        unset($source_class);

                }
        }
        /*
	print '<h2><u>Data Sources</u></h2>
            <p>Categories: <select class="cats" multiple="multiple" size="2">';
        
        foreach($groups_list as $key => $data) {
            $selected = '';
            echo '<option value="'.$key.'" '.$selected.'>'.$key.'</option>';
        }
         * 
         */

print '</select></p>
		<p>Select which data source(s) to use for your lookups, and the order in which you want them used:</p>
		<form method="POST" action="javascript:Ht_Generate_List(\'\',\''.$scheme.'\');" name="CIDSources">
			<div id="CIDSourcesList"></div>
			<br><br>
		</form>
		<table border="0">
			<tr>
				<td valign="top">
					<form method="POST" action="" name="Superfecta">
					<input type="hidden" name="scheme_name_orig" value="'.substr($scheme,5).'">
					<table border="0" id="table1" cellspacing="1">
						<tr>
							<td><a href="javascript:return(false);" class="info"><strong>Scheme Name:</strong><span>Duplicate Scheme names not allowed.</span></a></td>
							<td><input type="text" name="scheme_name" size="23" maxlength="20" value="'.substr($scheme,5).'"></td>
						</tr>
						<tr>
							<td colspan="2"><font face="Arial"><br><u>General Options</font></u></td>
						</tr>
						<tr>
							<td valign="top"><a href="javascript:return(false);" class="info">DID Rules<span>Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.<br><br>This rule trys both absolute and pattern matching (eg "_2[345]X", to match a range of numbers). (The "_" underscore is optional.)</span></a>:</td>
							<td>
								<textarea tabindex="1" cols="20" rows="5" name="DID">'.(isset($conf['DID']) ? $conf['DID'] : '' ).'</textarea>
							</td>
						</tr>
						<tr>
							<td valign="top">
							<a href="javascript:return(false);" class="info">CID Rules<span>Incoming calls with CID matching the patterns specified here will use this CID Scheme. If this is left blank, this scheme will be used for any CID. It can be used to add or remove prefixes.<br>
							<strong>Many sources require a specific number of digits in the phone number. It is recommended that you use the patterns to remove excess country code data from incoming CID to increase the effectiveness of this module.</strong><br>
							Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched pattern will be executed and the remaining rules will not be acted on.<br /><br /><b>Rules:</b><br />
							<strong>X</strong>&nbsp;&nbsp;&nbsp; matches any digit from 0-9<br />
							<strong>Z</strong>&nbsp;&nbsp;&nbsp; matches any digit from 1-9<br />
							<strong>N</strong>&nbsp;&nbsp;&nbsp; matches any digit from 2-9<br />
							<strong>[1237-9]</strong>&nbsp;   matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br />
							<strong>.</strong>&nbsp;&nbsp;&nbsp; wildcard, matches one or more characters (not allowed before a | or +)<br />
							<strong>|</strong>&nbsp;&nbsp;&nbsp; removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some one dialed "6135551234" but would only pass "5551234" to the Superfecta look up.)<br><strong>+</strong>&nbsp;&nbsp;&nbsp; adds a dialing prefix to the number (for example, 1613+NXXXXXX would match when someone dialed "5551234" and would pass "16135551234" to the Superfecta look up.)<br /><br />
							You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match "016065551234" and dial it as "0116065551234" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing.</span></a>:
							</td>
								<td valign="top">
								<textarea tabindex="2" id="dialrules" cols="20" rows="5" name="CID_rules">'.(isset($conf['CID_rules']) ? $conf['CID_rules'] : '' ).'</textarea>
						 		</td>
						</tr>
                                                <tr>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                </tr>
						<tr>
							<td><a href="javascript:return(false);" class="info">Lookup Timeout<span>Specify a timeout in seconds for each source. If the source fails to return a result within the alloted time, the script will move on.</span></a></td>
							<td><input type="text" name="Curl_Timeout" size="4" maxlength="5" value="'.$conf['Curl_Timeout'].'"></td>
						</tr>
						<tr>
							<td><a href="javascript:return(false);" class="info">Superfecta Processor<span>These are the types of Superfecta Processors:<br /><strong>MULTI:</strong> Multifecta, runs all sources at the same time<br /><strong>PCNTL:</strong> not working yet<br /><strong>SINGLE:</strong> Runs all sources in specified order, like old superfecta</span></a></td>
							<td>
                                                            <select name="processor">';

                                                            foreach($list as $data) {
                                                                echo $data;
                                                            }

                                                            print '</select>
							</td>
						</tr>
						<tr>
							<td><a href="javascript:return(false);" class="info">Multifecta Timeout<span>Specify a timeout in seconds defining how long multifecta will obey the source priority. After this timeout, the first source to respond with a CNAM will be taken, until "Lookup Timeout" is reached.</span></a></td>
							<td><input type="text" name="multifecta_timeout" size="4" maxlength="5" value="'.$conf['multifecta_timeout'].'"></td>
						</tr>
						<tr>
							<td><a href="javascript:return(false);" class="info">CID Prefix URL<span>If you wish to prefix information on the caller id you can specify a url here where that prefix can be retrieved.<br>The data will not be parsed in any way, and will be truncated to the first 10 characters.<br>Example URL: http://www.example.com/GetCID.php?phone_number=[thenumber]<br>[thenumber] will be replaced with the full 10 digit phone number when the URL is called.</span></a></td>
							<td><input type="text" name="Prefix_URL" size="23" maxlength="255" value="'.(isset($conf['Prefix_URL'])? $conf['Prefix_URL'] : '' ).'"></td>
						</tr>
                                               <tr>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                                <tr>
							<td><a href="javascript:return(false);" class="info">SPAM Text<span>This text will be prepended to Caller ID information to help you identify calls as SPAM calls.</span></a></td>
							<td><input type="text" name="SPAM_Text" size="23" maxlength="20" value="'.$conf['SPAM_Text'].'"></td>
						</tr>
						<tr>
							<td><a href="javascript:return(false);" class="info">SPAM Text Substituted<span>When enabled, the text entered in "SPAM Text" (above) will replace the CID completely rather than pre-pending the CID value.</span></a></td>
							<td>
								<input type="checkbox" name="SPAM_Text_Substitute" value="Y"' . ( ( (isset($conf['SPAM_Text_Substitute'])) && ($conf['SPAM_Text_Substitute'] == 'Y') ) ? 'checked' : '' ) . '>
							</td>
						</tr>
						<tr>
							<td><a href="javascript:return(false);" class="info">Enable SPAM Interception<span>When enabled, Spam calls can be diverted or terminated.</span></a></td>
							<td>
								<input type="checkbox" onclick="toggleInterceptor()" name="enable_interceptor" value="Y" ' . $spam_int . '>
							</td>
						</tr>
					</table>
					<table id="InterceptorVector" border="0">
			<tr>
				<td><a href="javascript:return(false);" class="info">SPAM Send Threshold<span>This is the threshold to send the call to the specified destination below</span></a></td>
				<td><input type="text" name="SPAM_threshold" size="4" maxlength="2" value="'.$conf['SPAM_threshold'].'"></td>
			</tr>
                        <tr class="incerceptorCell">
                                <td colspan="2">Send Spam Call To:</td>
                        </tr>
                        <tr class="incerceptorCell">
                                <td colspan="2">'.drawselects($goto,0,FALSE,FALSE).'</td>
                        </tr>
					</table>
					<p><a target="_blank" href="modules/superfecta/disclaimer.html">(License Terms)&nbsp; </a><input type="submit" value="Agree and Save" name="Save"></p>
					<p style="font-size:12px;">(* By clicking on either the &quot;Agree and Save&quot;<br>button, or the &quot;Debug&quot; button on this form<br>you are agreeing to the Caller ID Superfecta<br>Licensing Terms.)</p>
					</form>
				</td>
				<td valign="top">
					<form name="debug_form" action="javascript:Ht_debug(document.forms.debug_form.thenumber.value,'.$did_test_script.'document.forms.debug_form.Allscheme.checked,document.forms.debug_form.debug.value);">
						<p>Test a phone number against the selected sources.<br>
						'.$did_test_html.'
						<a href="javascript:return(false);" class="info">Phone Number:<span>Phone number to test this scheme against.</span></a> <input type="text" size="15" maxlength="20" name="thenumber"> <input type="submit" value="Debug"><br>
						<font size=2><input type="checkbox" name="Allscheme" value="All">
						<a href="javascript:return(false);" class="info">Test all CID schemes<span>When enabled, the debug function will test the number entered against all of the configured CID schemes.<br>When disabled, debug only checks up to the first scheme that provides positive results.</span></a> <br/>Debug Level:<select name="debug" id="debug_level"><option value="0">NONE</option><option value="1" selected>INFO</option><option value="2">WARN</option><option value="3">ALL</option></select></font></p>
					</form>
					<div id="debug" style="background-color: #E0E0E0; width:100%"></div>
				</td>
			</tr>
		</table>
		';
}

//uncomment line below to see the available array values in $module_info.
//print '<pre>'.print_r($module_info,true).'</pre>';

print '<p align="center" style="font-size:10px;">This Project is now hosted/maintained at <a href="https://github.com/tm1000/Caller-ID-Superfecta">https://github.com/tm1000/Caller-ID-Superfecta</a> Feel free to fork/help/complain<br />The CallerID Superfecta module was maintained by the Community at<a target="_blank" href="http://projects.colsolgrp.net/projects/show/superfecta"> CSG Software Projects</a>, and by the forum users at <a target="_blank" href="http://www.pbxinaflash.com/forum">PBX In A Flash Forums</a>.<br>The Superfecta was Modularized for FreePBX by Tony Shiffer, based on an earlier (non module) work by <a target="_blank" href="http://www.nerdvittles.com">Ward Mundy</a>.<br>  
		<a target="_blank" href="https://github.com/tm1000/Caller-ID-Superfecta/wiki">This Module\'s wiki pages can be found here.</a>
		<br><br><a target="_blank" href="https://github.com/tm1000/Caller-ID-Superfecta/issues?sort=created&amp;direction=desc&amp;state=open">Module version '.$module_info['module']['version'].'</a></p>';
?>
<script language="javascript">
<!--
var isWorking = false;
var divname = '';
var http = getHTTPObject();

$(".cats").change(function () {
      var str =new Array();
      var i = 0;
      $(".cats option:selected").each(function () {
            str[i] = $(this).text();
            i ++; 
          });
      Ht_Generate_List('','<?php echo $scheme;?>',str);
    })
    .change();
    
function array2json(arr) {
    var parts = [];
    var is_list = (Object.prototype.toString.apply(arr) === '[object Array]');

    for(var key in arr) {
    	var value = arr[key];
        if(typeof value == "object") { //Custom handling for arrays
            if(is_list) parts.push(array2json(value)); /* :RECURSION: */
            else parts[key] = array2json(value); /* :RECURSION: */
        } else {
            var str = "";
            if(!is_list) str = '"' + key + '":';

            //Custom handling for multiple data types
            if(typeof value == "number") str += value; //Numbers
            else if(value === false) str += 'false'; //The booleans
            else if(value === true) str += 'true';
            else str += '"' + value + '"'; //All other things
            // :TODO: Is there any more datatype we should be in the lookout for? (Functions?)

            parts.push(str);
        }
    }
    var json = parts.join(",");
    
    if(is_list) return '[' + json + ']';//Return numerical JSON
    return '{' + json + '}';//Return associative JSON
}

  function is_null(input){
    return input==null;
  }

function getHTTPObject()
{
	var xmlhttp;
	//do not take out this section of code that appears to be commented out...if you do the guns stop working.
	/*@cc_on
	@if (@_jscript_version >= 5)
	try
	{
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	}
	catch (e)
	{
		try
		{
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		catch (E)
		{
			xmlhttp = false;
		}
	}
	@else
	{
		xmlhttp = false;
	}
	@end @*/

	if(!xmlhttp && typeof XMLHttpRequest != 'undefined')
	{
		try
		{
			xmlhttp = new XMLHttpRequest();
		}
		catch (e)
		{
			xmlhttp = false;
		}
	}
	return xmlhttp;
}

function Ht_Response()
{
	if (http.readyState == 4)
	{
		document.getElementById(divname).innerHTML = http.responseText;
		isWorking = false;
		reset_infoboxes();
	}
}

function Ht_Generate_List(first_run,scheme)
{
	first_run = first_run || "";
	scheme = scheme || "";
        /*
        if(cat == 'NULL') {
            var cat =new Array();
            var i = 0;
            $(".cats option:selected").each(function () {
                cat[i] = $(this).text();
                i ++; 
            });
        } else {
            cat = array2json(cat);
        }
	var poststr = "first_run=" + first_run + "&scheme=" + scheme + "&cats=" + cat;
        */
       	var poststr = "first_run=" + first_run + "&scheme=" + scheme;

	if(document.forms.CIDSources.src_list)
	{
		var this_form = document.forms.CIDSources;
		var elem = this_form.elements;
		for(var i = 0; i < elem.length; i++)
		{
			if(elem[i].type == 'checkbox')
			{
				if(elem[i].checked == true)
				{
					poststr = poststr + "&" + elem[i].name + "=on";
				}
				else
				{
					poststr = poststr + "&" + elem[i].name + "=off";
				}
			}
			else if(elem[i].type != 'radio')
			{
				poststr = poststr + "&" + elem[i].name + "=" + elem[i].value;
			}
		}

		var CIDList = this_form.src_list.value.split(',');
		var array_count = 0;
		while (array_count < CIDList.length)
		{
			var CIDsource = CIDList[array_count];
			array_count+=1;

			if(CIDsource != '')
			{
				var this_value = 0;
				eval('if(document.forms.CIDSources.' + CIDsource + ') { this_value = this_form.' + CIDsource + '; }');
				if(this_value != 0)
				{
					for(var i=0; i < this_value.length; i++)
					{
						if(this_value[i].checked)
						{
							this_value = this_value[i].value;
						}
					}
				}
				poststr = poststr + "&" + CIDsource + "=" + this_value;
			}
		}
	}

	if(!isWorking)
	{
		isWorking = true;
		divname = 'CIDSourcesList';
		http.open("POST", "modules/superfecta/sources.php", true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = Ht_Response;
		http.send(poststr);
	}
	else
	{
		setTimeout("Ht_Generate_List('" + first_run + "')",100);
	}
	toggleInterceptor();
}

function Ht_debug(thenumber,testdid,checkall,debuglevel)
{
	thenumber = thenumber || "";
	testdid = testdid || "";
	checkall = checkall || false;
	var poststr = "debug=" + debuglevel + "&thenumber=" + thenumber + "&testdid=" + testdid;
	if(!checkall)
	{
		poststr = poststr + "&scheme=<?php print $scheme ?>";
	}
        else
        {
                poststr = poststr + "&scheme=base_ALL_ALL";
        }

	if(!isWorking)
	{
		isWorking = true;
		divname = 'debug';
		document.getElementById(divname).innerHTML = "<img src='modules/superfecta/loading.gif' style='margin: 20px auto 20px 150px;'>";
		http.open("POST", "modules/superfecta/includes/callerid.php", true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.setRequestHeader("Content-length", poststr.length);
		http.setRequestHeader("Connection", "close");
		http.onreadystatechange = Ht_Response;
		http.send(poststr);
	}
	else
	{
		setTimeout("Ht_debug('" + thenumber + "','" + testdid + "'," + checkall + ")",100);
	}
}

function reset_infoboxes(){
	body_loaded();
	// test for a function that seems to only be in freepbx 2.8+
	if(typeof window.tabberAutomaticOnLoad == 'function') {
		$("a.info").hover(function () {
			var pos = $(this).offset();
			var left = (200 - pos.left) + "px";
			$(this).find("span").css("left", left).stop(true, true).delay(500).animate({
				opacity: "show"
			}, 750);
		}, function () {
		$(this).find("span").stop(true, true).animate({
			opacity: "hide"
			}, "fast");
		});
	}
}

function toggleInterceptor() {
	
	var row = document.getElementById("InterceptorVector");
	if(document.Superfecta.enable_interceptor.checked && row)
	{
    	row.style.display = '';
	}
	else if(row)
	{
         row.style.display = 'none';
	}
}

function decision(message, url)
{
	if(confirm(message)) location.href = url;
}

<?php
if(($scheme != "") && ($scheme != "new"))
{
	print 'Ht_Generate_List(1,"'.$scheme.'","ALL");';
}
print '
//-->
</script>';
