<?php
/*** Original script by Nerd Vittles. (Google for Caller Id Trifecta)
    3/12/2009  	Put into module format by Tony Shiffer & Jerry Swordsteel
			commented out fixed variables.  Added db code to get values from db.
	3/30/2009  	New SugarCRM by jpeterman is now included.
	4/8/2009  	Removed dependancy on default id/pw for db connection. now parses amportal.conf
	5-8-2009	Version 2.0.0 Major update - Tickets: Tickets: #7, #10, #15, #17, #36, and #19.(projects.colsolgrp.net)(jjacobs)
	8-18-2009	Version 2.2.0  CID Schemes and online update for data sources (projects.colsolgrp.net)(jjacobs)
	10-26-2009  Version 2.2.2  http://projects.colsolgrp.net/versions/show/55 (projects.colsolgrp.net) (patrick_elx)
***/

$debug_val = (isset($_REQUEST['debug'])) ? $_REQUEST['debug'] : '';
$debug = ($debug_val == 'yes') ? true : false;
if($debug){
	// If debugging, report all errors
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
}

$caller_id = '';
$charsetIA5 = true;
$first_caller_id = '';
$prefix = '';
$cache_found = false;
$spam = false;
$winning_source = '';
$usage_mode = 'get caller id';
$src_array = array();
$thenumber_orig = (isset($_REQUEST['thenumber'])) ? trim($_REQUEST['thenumber']) : '';
if(($thenumber_orig == '') && isset($argv[1])){
	$thenumber_orig = $argv[1];
}
$testdid = (isset($_REQUEST['testdid'])) ? trim($_REQUEST['testdid']) : '';
if(($testdid == '') && isset($argv[2])){
	$testdid = $argv[2];
}
$scheme = (isset($_REQUEST['scheme'])) ? trim($_REQUEST['scheme']) : '';
//$thenumber_orig = ereg_replace('[^0-9]+', '', $thenumber_orig);

if($debug)
{
	$start_time_whole = mctime_float();
	$end_time_whole = 0;
}

//New code for FreePBX 2.9 -- Andrew Nagy (tm1000)
if(file_exists("/etc/freepbx.conf")) {
	//This is FreePBX 2.9+
	if($debug) {
		echo "<br/><strong>Detected FreePBX version is at least 2.9</strong><br/>";
	}
	require("/etc/freepbx.conf");
	global $db,$astman,$amp_conf;
} elseif(file_exists("/etc/asterisk/freepbx.conf")) {
	//This is FreePBX 2.9+
	if($debug) {
		echo "<br/><strong>Detected FreePBX version is at least 2.9</strong><br/>";
	}
	require("/etc/asterisk/freepbx.conf");
	global $db,$astman,$amp_conf;	
} else {
	//This is > FreePBX 2.8
	if($debug) {
		echo "<br/><strong>Detected FreePBX version is at most 2.8</strong><br/>";
	}
	require_once("../../../functions.inc.php");
	require_once 'DB.php';
	define("AMP_CONF", "/etc/amportal.conf");

	$amp_conf = parse_amportal_conf(AMP_CONF);
	if(count($amp_conf) == 0)
	{
		fatal("FAILED");
	}

	$dsn = array(
	    'phptype'  => 'mysql',
	    'username' => $amp_conf['AMPDBUSER'],
	    'password' => $amp_conf['AMPDBPASS'],
	    'hostspec' => $amp_conf['AMPDBHOST'],
	    'database' => $amp_conf['AMPDBNAME'],
	);
	$options = array();
	$db =& DB::connect($dsn, $options);
	if(PEAR::isError($db))
	{
		die($db->getMessage());
	}

	//connect to the asterisk manager
	require_once('../../../common/php-asmanager.php');
	$astman	= new AGI_AsteriskManager();	
}
//End new FreePBX 2.9 code.

// attempt to connect to asterisk manager proxy
if(!isset($amp_conf["ASTMANAGERPROXYPORT"]) || !$res = $astman->connect("127.0.0.1:".$amp_conf["ASTMANAGERPROXYPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], 'off'))
{
	// attempt to connect directly to asterisk, if no proxy or if proxy failed
	if (!$res = $astman->connect("127.0.0.1:".$amp_conf["ASTMANAGERPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], 'off'))
	{
		// couldn't connect at all
		unset( $astman );
	}
}

$param = array();
$query = "SELECT * FROM superfectaconfig";
$res = $db->query($query);
while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
{
	$param[$row['source']][$row['field']] = $row['value'];
}

if($debug)
{
	print "Debugging Enabled, will not stop after first result.<br>\n";
}

//loop through schemes
$query = "SELECT source	FROM superfectaconfig WHERE field = 'order' AND value > 0";

if($debug && ($scheme != ""))
{
	$query .= " AND		source = '$scheme'";
}
$query .= " ORDER BY value";
$res = $db->query($query);
if(DB::isError($res) && $debug)
{
	print 'The database query of:<br>'.$query.'<br>failed with an error of:<br>'.$res->getMessage();
}
else
{

	//get the DID for this call using the PHP Asterisk Manager
	$DID = "";
	$value = $astman->command('core show channels concise');
	$chan_array = preg_split("/\n/",$value['data']);
	foreach($chan_array as $val)
	{
		$this_chan_array = explode("!",$val);
		if(isset($this_chan_array[7]))
		{
			$this_chan_array[7]=trim($this_chan_array[7]);
	//		if($thenumber_orig == substr($this_chan_array[7],-10))
			if($thenumber_orig == $this_chan_array[7])
			{
				$value = $astman->command('core show channel '.$this_chan_array[0]);
				$this_array = preg_split("/\n/",$value['data']);
				foreach($this_array as $val2)
				{
					if(strpos($val2,'FROM_DID=') !== false)
					{
						$DID = trim(str_replace('FROM_DID=','',$val2));
						break;
					}
				}
	
				//break out if the value is set.
				if($DID != '')
				{
					break;
				}
			}
		}
	}
	// If a test DID number has been specified, and we're in debug mode, use it.
	if((strlen(trim($testdid))) && ($debug)){
		$DID = $testdid;
	}

	// Loop over each scheme
	while ($res && ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)))
	{
		$this_scheme = $row['source'];
		$run_this_scheme = true;
		$thenumber = ereg_replace('[^0-9]+', '', $thenumber_orig);

		if($debug)
		{
			print "<hr>Processing ".substr($this_scheme,5)." Scheme.<br>\n";
		}
		//trying to push some info to the CLI
		$astman->command('VERBOSE "Processing '.substr($this_scheme,5).' Scheme." 3');


		// Determine if this is the correct DID, if this scheme is limited to a DID.

		$rule_match = match_pattern_all( (isset($param[$this_scheme]['DID'])) ? $param[$this_scheme]['DID'] : '', $DID );
		if($rule_match['number']){
			if($debug){print "Matched DID Rule: '".$rule_match['pattern']."' with '".$rule_match['number']."'<br>\n";}
		}elseif($rule_match['status']){
			if($debug){print "No matching DID rules.<br>\n";}
			$run_this_scheme = false;
		}


		// Determine if the CID matches any patterns defined for this scheme

		$rule_match = match_pattern_all((isset($param[$this_scheme]['CID_rules']))?$param[$this_scheme]['CID_rules']:'', $thenumber );
		if($rule_match['number'] && $run_this_scheme){
			if($debug){print "Matched CID Rule: '".$rule_match['pattern']."' with '".$rule_match['number']."'<br>\n";}
			$thenumber = $rule_match['number'];
		}elseif($rule_match['status'] && $run_this_scheme){
			if($debug){print "No matching CID rules.<br>\n";}
			$run_this_scheme = false;
		}

		// Run the scheme
		
		if($run_this_scheme)
		{
			$curl_timeout = $param[$this_scheme]['Curl_Timeout'];

			//if a prefix lookup is enabled, look it up, and truncate the result to 10 characters
			if( (isset($param[$this_scheme]['Prefix_URL'])) && (trim($param[$this_scheme]['Prefix_URL']) != ''))
			{
				if($debug)
				{
					$start_time = mctime_float();
				}

				$prefix = get_url_contents(str_replace("[thenumber]",$thenumber,$param[$this_scheme]['Prefix_URL']));

				if($debug)
				{
					print "Prefix Url defined ...\n";
					if($prefix !='')
					{
						print 'returned: '.$prefix."<br>\n";
					}
					else
					{
						print "result was empty<br>\n";
					}
					print "result <img src='images/scrollup.gif'> took ".number_format((mctime_float()-$start_time),4)." seconds.<br>\n<br>\n";
				}
			}

			//run through the specified sources
			$src_array = explode(',',$param[$this_scheme]['sources']);
			$theoriginalnumber = $thenumber;
			if ($theoriginalnumber !='')
			{
			   foreach($src_array as $source_name)
			   {
				$thenumber = $theoriginalnumber;
				if($debug)
				{
					$start_time = mctime_float();
				}
				$caller_id = '';
				$run_param = isset($param[substr($this_scheme,5).'_'.$source_name]) ? $param[substr($this_scheme,5).'_'.$source_name] : array();

				eval('include("source-'.$source_name.'.php");');
				$caller_id = _utf8_decode($caller_id);
				if(($first_caller_id == '') && ($caller_id != ''))
				{
					$first_caller_id = $caller_id;
					$winning_source = $source_name;
					if($debug)
					{
						$end_time_whole = mctime_float();
					}
				}

				if($debug)
				{
					if($caller_id != '')
					{
						print "'" . utf8_encode($caller_id)."'<br>\nresult <img src='images/scrollup.gif'> took ".number_format((mctime_float()-$start_time),4)." seconds.<br>\n<br>\n";
					}
					else
					{
						print "result <img src='images/scrollup.gif'> took ".number_format((mctime_float()-$start_time),4)." seconds.<br>\n<br>\n";
					}
				}
				else if($caller_id != '')
				{
					break;
				}
			   }
			} else
			{
			  if($debug)
				{
					print "The caller IDnumber '".$thenumber_orig."' did not contain number. Lookup stopped <br>";
				}
			}


			$prefix = ($prefix != '') ? $prefix.':' : '';
			if($spam)
			{
				if(isset($param[$this_scheme]['SPAM_Text_Substitute']) && $param[$this_scheme]['SPAM_Text_Substitute'] == 'Y')
				{
					$first_caller_id = $param[$this_scheme]['SPAM_Text'];
				}
				else
				{
					$first_caller_id = "{$param[$this_scheme]['SPAM_Text']}:".str_replace("{$param[$this_scheme]['SPAM_Text']}:", '', $first_caller_id);
				}
			}
		}

		if($first_caller_id != '')
		{
			break;
		}
	}
}

//post-processing
if($debug)
{
	print "Post CID retrieval processing.<br>\n<br>\n";
}

//remove unauthorized character in the caller id
if ($first_caller_id !='')
	{
		//$first_caller_id = _utf8_decode($first_caller_id);
		$first_caller_id = strip_tags($first_caller_id );
		$first_caller_id = trim ($first_caller_id);
		if ($charsetIA5)
		{
			$first_caller_id = stripAccents($first_caller_id);
		}
		$first_caller_id = preg_replace ( "/[\";']/", "", $first_caller_id);
		//limit caller id to the first 60 char
		$first_caller_id = substr($first_caller_id,0,60);
	}

$usage_mode = 'post processing';
foreach($src_array as $source_name)
{
	$thenumber = $theoriginalnumber;
	$run_param = isset($param[substr($this_scheme,5).'_'.$source_name]) ? $param[substr($this_scheme,5).'_'.$source_name] : array();
	eval('include("source-'.$source_name.'.php");');
}

if($debug)
{
	print "<b>Returned Result would be: ";
	$first_caller_id = utf8_encode($first_caller_id);
}
print $prefix.$first_caller_id;

if($debug)
{
	$end_time_whole = ($end_time_whole == 0) ? mctime_float() : $end_time_whole;
	print "<br>\nresult <img src='images/scrollup.gif'> took ".number_format(($end_time_whole-$start_time_whole),4)." seconds.</b>";
}

$astman->disconnect();


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
	$string = strtr($string,"äåéöúûü•µ¿¡¬√ƒ≈∆«»… ÀÃÕŒœ–—“”‘’÷ÿŸ⁄€‹›ﬂ‡·‚„‰ÂÊÁËÈÍÎÏÌÓÔÒÚÛÙıˆ¯˘˙˚¸˝ˇ","SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
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
