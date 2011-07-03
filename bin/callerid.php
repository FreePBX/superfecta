<?php
/***
TODO:  Move all DB, FreeBPX and Asterisk specific operations into functions

Original script by Nerd Vittles. (Google for Caller Id Trifecta)
	03/12/2009	Put into module format by Tony Shiffer & Jerry Swordsteel
			commented out fixed variables.  Added db code to get values from db.
	03/30/2009  	New SugarCRM by jpeterman is now included.
	04/08/2009  	Removed dependancy on default id/pw for db connection. now parses amportal.conf
	05-08-2009	Version 2.0.0  Major update - Tickets: Tickets: #7, #10, #15, #17, #36, and #19.(projects.colsolgrp.net)(jjacobs)
	08-18-2009	Version 2.2.0  CID Schemes and online update for data sources (projects.colsolgrp.net)(jjacobs)
	10-26-2009  	Version 2.2.2  http://projects.colsolgrp.net/versions/show/55 (projects.colsolgrp.net) (patrick_elx)
	01-03-2010  	Version 2.3.0  Updates to remove need for Caller ID Lookup module
	01-04-2010  	Version 2.3.0  Updates for running multiple sources at the same time (Multifecta)
***/
require_once('../config.php');

//Determine CLI or HTTP
if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
	$cli = true;
	$shortopts  = "";
	$shortopts .= "s:m:n:r:";  // Required value
	$shortopts .= "d"; // These options do not accept values
	$options = getopt($shortopts);
	if(isset($options)) {
		$scheme_name = "base_".$options['s'];
		$debug = isset($options['d']) ? true : false;
		$multifecta_id = isset($options['m']) ? $options['m'] : false;
		$thenumber_orig = isset($options['n']) ? $options['n'] : false;
		$source = isset($options['r']) ? $options['r'] : false;
	}
} else {
	$cli = false;
	$scheme_name = "base_".(isset($_REQUEST['scheme'])) ? trim($_REQUEST['scheme']) : '';
	$debug = (((isset($_REQUEST['debug'])) ? $_REQUEST['debug'] : '') == 'yes') ? true : false;
	$thenumber_orig = (isset($_REQUEST['thenumber'])) ? trim($_REQUEST['thenumber']) : '';
}

//Die on Scheme unknown
if(trim($scheme_name) == '') {
	die("No Scheme Assigned/Known!");
}

//Get Scheme Params
$param = array();
$query = "SELECT * FROM superfectaconfig";
$res = $db->query($query);
if (DB::IsError($res)){
	die("Unable to load scheme parameters: " . $res->getMessage() .  "<br>");
}
while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
{
	$param[$row['source']][$row['field']] = $row['value'];
}

if(!array_key_exists($scheme_name,$param)) {
	die('Scheme Does not Exist!');
}

$scheme_param = $param[$scheme_name];

require_once('superfecta_base.php');
if(isset($scheme_param['enable_multifecta'])) {
	require_once('superfecta_multi.php');
	$superfecta = NEW superfecta_multi($multifecta_id,$db,$amp_conf,$debug,$thenumber_orig,$scheme_name,$scheme_param,$source);
	$superfecta->type = 'MULTI';
} else {
	require_once('superfecta_single.php');
	$superfecta = NEW superfecta_single($db,$amp_conf,$debug,$thenumber_orig,$scheme_name,$scheme_param);
	$superfecta->type = 'SUPER';
}
$superfecta->cli = $cli;

if(($superfecta->debug) && (($superfecta->type == 'SUPER') || (($superfecta->type == 'MULTI') && ($superfecta->multi_type == 'PARENT')))){
	// If debugging, report all errors
	error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	ini_set('display_errors', '1');
	$superfecta->out("<strong>Debug is on</strong><br>\n");
	$superfecta->out("<strong>The Original Number: </strong>". $superfecta->thenumber_orig);
	$superfecta->out("<strong>The Scheme: </strong>". $superfecta->scheme_name);
	$superfecta->out("<strong>Scheme Type: </strong>".$superfecta->type."FECTA");
	$superfecta->out("<strong>is CLI: </strong>");
	$superfecta->out($cli ? 'true' : 'false');
	$start_time_whole = mctime_float();
	$end_time_whole = 0;
	$superfecta->out("<b>Debugging Enabled, will not stop after first result.");
	$superfecta->out("Scheme Variables:</b><pre>". print_r($superfecta->scheme_param,TRUE) . "</pre>");
}
$superfecta->thenumber = ereg_replace('[^0-9]+', '', $superfecta->thenumber_orig);

$run_this_scheme = true;

if(($superfecta->type == 'SUPER') || (($superfecta->type == 'MULTI') && ($superfecta->multi_type == 'PARENT'))) {
	// Determine if this is the correct DID, if this scheme is limited to a DID.
	$rule_match = match_pattern_all( (isset($scheme_param['DID'])) ? $scheme_param['DID'] : '', $DID );
	if($rule_match['number']){
		if($superfecta->debug){print "Matched DID Rule: '".$rule_match['pattern']."' with '".$rule_match['number']."'<br>\n";}
	}elseif($rule_match['status']){
		if($superfecta->debug){print "No matching DID rules.<br>\n";}
		$run_this_scheme = false;
	}

	// Determine if the CID matches any patterns defined for this scheme
	$rule_match = match_pattern_all((isset($scheme_param['CID_rules']))?$scheme_param['CID_rules']:'', $superfecta->thenumber );
	if($rule_match['number'] && $run_this_scheme){
		if($superfecta->debug){print "Matched CID Rule: '".$rule_match['pattern']."' with '".$rule_match['number']."'<br>\n";}
		$superfecta->thenumber = $rule_match['number'];
	}elseif($rule_match['status'] && $run_this_scheme){
		if($superfecta->debug){print "No matching CID rules.<br>\n";}
		$run_this_scheme = false;
	}

	$superfecta->curl_timeout = $scheme_param['Curl_Timeout'];

	//if a prefix lookup is enabled, look it up, and truncate the result to 10 characters
	///Clean these up, set NULL values instead of blanks then don't check for ''
	$superfecta->prefix = '';
	if((isset($scheme_param['Prefix_URL'])) && (trim($scheme_param['Prefix_URL']) != ''))
	{
		if($superfecta->debug)
		{
			$start_time = mctime_float();
		}
	
		$superfecta->prefix = get_url_contents(str_replace("[thenumber]",$superfecta->thenumber,$scheme_param['Prefix_URL']));

		if($superfecta->debug)
		{
			print "Prefix Url defined ...\n";
			if($superfecta->prefix !='')
			{
				print 'returned: '.$superfecta->prefix."<br>\n";
			}
			else
			{
				print "result was empty<br>\n";
			}
			print "result <img src='images/scrollup.gif'> took ".number_format((mctime_float()-$start_time),4)." seconds.<br>\n<br>\n";
		}
	}

	if($run_this_scheme) {
		$callerid = $superfecta->get_results();
	
		if ($callerid !='')
		{
			//$first_caller_id = _utf8_decode($first_caller_id);
			$callerid = strip_tags($callerid );
			$callerid = trim ($callerid);
			if ($superfecta->charsetIA5)
			{
				$callerid = stripAccents($callerid);
			}
			$callerid = preg_replace ( "/[\";']/", "", $callerid);
			//limit caller id to the first 60 char
			$callerid = substr($callerid,0,60);
		}
	
		$superfecta->send_results($callerid);
	
		if(!$superfecta->debug) {
			echo $superfecta->prefix.$callerid;
		} else {
			print "<b>Returned Result would be: ";
			$callerid = utf8_encode($superfecta->prefix.$callerid);
			print $callerid;
			$end_time_whole = ($end_time_whole == 0) ? mctime_float() : $end_time_whole;
			print "<br>\nresult <img src='images/scrollup.gif'> took ".number_format(($end_time_whole-$start_time_whole),4)." seconds.</b>";
		}
	}
} elseif(($superfecta->type == 'MULTI') && ($superfecta->multi_type == 'CHILD')) {
	$superfecta->get_results();
}

/**
Search an array of area codes against phone number to find one that matches.
Return an array with the area code, area name and remaining phone number
*/
function cisf_find_area ($area_array, $full_number)
{
	$largest_match = 0;
	$match = false;
        foreach ($area_array as $area => $area_code) {
		$area_length = strlen($area_code);
                if((substr($full_number,0,$area_length)==$area_code) && ($area_length > $largest_match)) {
                        $match = array(
				'area'=>$area,
				'area_code'=>$area_code,
				'number'=>substr($full_number,$area_length)
			);
			$largest_match = $area_length;
                }
        }
        return $match;
}

/**
Encode an array for transmission in http request
*/
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
	global $debug,$curl_timeout;
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
	curl_setopt($crl,CURLOPT_CONNECTTIMEOUT,$curl_timeout);
	curl_setopt($crl,CURLOPT_FAILONERROR,true);
	curl_setopt($crl,CURLOPT_TIMEOUT,$curl_timeout);
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

function mctime_float()
{
	 list($usec, $sec) = explode(" ", microtime());
	 return ((float)$usec + (float)$sec);
}

/** 
	Match a phone number against an array of patterns
	return array containing
	'pattern' = the pattern that matched
	'number' = the number that matched, after applying rules
	'status' = true if a valid array was supplied, false if not
	
*/

function match_pattern_all($array, $number){

	// If we did not get an array, it's probably a list. Convert it to an array.
	if(!is_array($array)){
		$array =  explode("\n",trim($array));		
	}

	$match = false;
	$pattern = false;
	
	// Search for a match
	foreach($array as $pattern){
		// Strip off any leading underscore
		$pattern = (substr($pattern,0,1) == "_")?trim(substr($pattern,1)):trim($pattern);
		if($match = match_pattern($pattern,$number)){
			break;
		}elseif($pattern == $number){
			$match = $number;
			break;
		}
	}

	// Return an array with our results
	return array(
		'pattern' => $pattern,
		'number' => $match,
		'status' => (isset($array[0]) && (strlen($array[0])>0))
	);
}

/**
	Parses Asterisk dial patterns and produces a resulting number if the match is successful or false if there is no match.
*/

function match_pattern($pattern, $number)
{
	global $debug;
	$pattern = trim($pattern);
	$p_array = str_split($pattern);
	$tmp = "";
	$expression = "";
	$new_number = false;
	$remove = "";
	$insert = "";
	$error = false;
	$wildcard = false;
	$match = $pattern?true:false;
	$regx_num = "/^\[[0-9]+(\-*[0-9])[0-9]*\]/i";
	$regx_alp = "/^\[[a-z]+(\-*[a-z])[a-z]*\]/i";

	// Try to build a Regular Expression from the dial pattern
	$i = 0;
	while (($i < strlen($pattern)) && (!$error) && ($pattern))
	{
		switch(strtolower($p_array[$i]))
		{
			case 'x':
				// Match any number between 0 and 9
				$expression .= $tmp."[0-9]";
				$tmp = "";
				break;
			case 'z':
				// Match any number between 1 and 9
				$expression .= $tmp."[1-9]";
				$tmp = "";
				break;
			case 'n':
				// Match any number between 2 and 9
				$expression .= $tmp."[2-9]";
				$tmp = "";
				break;
			case '[':
				// Find out if what's between the brackets is a valid expression.
				// If so, add it to the regular expression.
				if(preg_match($regx_num,substr($pattern,$i),$matches)
					||preg_match($regx_alp,substr(strtolower($pattern),$i),$matches))
				{
					$expression .= $tmp."".$matches[0];
					$i = $i + (strlen($matches[0])-1);
					$tmp = "";
				}
				else
				{
					$error = "Invalid character class";
				}
				break;
			case '.':
			case '!':
				// Match and number, and any amount of them
				if(!$wildcard){
					$wildcard = true;
					$expression .= $tmp."[0-9]+";
					$tmp = "";
				}else{
					$error = "Cannot have more than one wildcard";
				}
				break;
			case '+':
				// Prepend any numbers before the '+' to the final match
                                // Store the numbers that will be prepended for later use
				if(!$wildcard){
					if($insert){
						$error = "Cannot have more than one '+'";
					}elseif($expression){
						$error = "Cannot use '+' after X,Z,N or []'s";
					}else{
						$insert = $tmp;
						$tmp = "";
					}
				}else{
					$error = "Cannot have '+' after wildcard";
				}
				break;
			case '|':
				// Any numbers/expression before the '|' will be stripped
				if(!$wildcard){
					if($remove){
						$error = "Cannot have more than one '|'";
					}else{
						// Move any existing expression to the "remove" expression
						$remove = $tmp."".$expression;
						$tmp = "";
						$expression = "";
					}
				}else{
					$error = "Cannot have '|' after wildcard";
				}
				break;
			default:
				// If it's not any of the above, is it a number betwen 0 and 9?
				// If so, store in a temp buffer.  Depending on what comes next
				// we may use in in an expression, or a prefix, or a removal expression
				if(preg_match("/[0-9]/i",strtoupper($p_array[$i]))){
					$tmp .= strtoupper($p_array[$i]);
				}else{
					$error = "Invalid character '".$p_array[$i]."' in pattern";
				}
		}
		$i++;
	}
	$expression .= $tmp;
	$tmp = "";
	if($error){
		// If we had any error, report them
		$match = false;
		if($debug){print $error." - position $i<br>\n";}
	}else{
		// Else try out the regular expressions we built
		if($remove){
			// If we had a removal expression, se if it works
			if(preg_match("/^".$remove."/i",$number,$matches)){
				$number = substr($number,strlen($matches[0]));
			}else{
				$match = false;
			}
		}
		// Check the expression for the rest of the number
		if(preg_match("/^".$expression."$/i",$number,$matches)){
			$new_number = $matches[0];
		}else{
			$match = false;
		}
		// If there was a prefix defined, add it.
		$new_number = $insert . "" . $new_number;
		
	}
	if(!$match){
		// If our match failed, return false
		$new_number = false;
	}
	return $new_number;

}

function stripAccents($string)
{
	$string = html_entity_decode($string);
	$string = strtr($string,"ŠŒšœŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏĞÑÒÓÔÕÖØÙÚÛÜİßàáâãäåæçèéêëìíîïğñòóôõöøùúûüıÿ","SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
	$string = str_replace(chr(160), ' ', $string);
	return $string;
}

function isutf8($string)
{
	if (!function_exists('mb_detect_encoding')) {
		return false;
	} else {
		return (mb_detect_encoding($string."e")=="UTF-8");// added a character to the string to avoid the mb detect bug
	}
}

function _utf8_decode($string)
{
  	$string= html_entity_decode($string);
  	$tmp = $string;
	$count = 0;
	while (isutf8($tmp))
  	{
  		$tmp = utf8_decode($tmp);
		$count++;
	}

  	for ($i = 0; $i < $count-1 ; $i++)
  	{
    		$string = utf8_decode($string);
  	}
  	return $string;
}
?>
