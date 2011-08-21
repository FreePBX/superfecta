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
require_once(dirname(__FILE__)."/config.php");

//Determine CLI or HTTP
if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
	$cli = true;
	$shortopts  = "";
	$shortopts .= "d:s:m:n:r:i";  // Required value
	$options = getopt($shortopts);
	if(isset($options)) {
		$scheme_name_request = "base_".$options['s'];
		$debug = isset($options['d']) ? $options['d'] : 0;
		$multifecta_id = isset($options['m']) ? $options['m'] : false;
		$thenumber_orig = isset($options['n']) ? $options['n'] : false;
		$source = isset($options['r']) ? $options['r'] : false;
		$DID = isset($options['i']) ? $options['i'] : false;
	}
} else {
	$cli = false;
	$scheme_name_request = "base_".(isset($_REQUEST['scheme'])) ? trim($_REQUEST['scheme']) : '';
        $debug = isset($_REQUEST['debug']) ? $_REQUEST['debug'] : 0;
	$thenumber_orig = (isset($_REQUEST['thenumber'])) ? trim($_REQUEST['thenumber']) : '';
	$DID = (isset($_REQUEST['DID'])) ? trim($_REQUEST['DID']) : '';
}

//Die on Scheme unknown
if((trim($scheme_name_request) == '') OR ($scheme_name_request == 'base_ALL_ALL')) {
	if((!$cli) OR ($scheme_name_request == 'base_ALL_ALL')) {
		$sql = 'SELECT `source` FROM `superfectaconfig` WHERE `field` = CONVERT(_utf8 \'sources\' USING latin1) COLLATE latin1_swedish_ci';
		$data = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		$i=0;
		foreach($data as $list) {
			$scheme_name_array[$i] = $list['source'];
			$i++;
		}
	} else {
		die('No Scheme Assigned/Known!');
	}
} else {
	$scheme_name_array[0] = $scheme_name_request;
}

foreach($scheme_name_array as $list) {
	$scheme_name = $list;

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

	require_once(dirname(__FILE__).'/superfecta_base.php');

        switch($scheme_param['processor']) {
            case 'superfecta_multi.php':
                require_once(dirname(__FILE__).'/processors/superfecta_multi.php');
		//require_once('superfecta_pcntl.php');
		$superfecta = NEW superfecta_multi($multifecta_id,$db,$amp_conf,$astman,$debug,$thenumber_orig,$scheme_name,$scheme_param,$source);
		$superfecta->type = 'MULTI';
                break;
            case 'superfecta_single.php':
                require_once(dirname(__FILE__).'/processors/superfecta_single.php');
		$superfecta = NEW superfecta_single($db,$amp_conf,$astman,$debug,$thenumber_orig,$scheme_name,$scheme_param);
		$superfecta->type = 'SUPER';
                break;
            case 'superfecta_pcntl.php' :
                require_once(dirname(__FILE__).'/processors/superfecta_single.php');
		$superfecta = NEW superfecta_single($db,$amp_conf,$astman,$debug,$thenumber_orig,$scheme_name,$scheme_param);
		$superfecta->type = 'SUPER';
                //$superfecta->outn("PCNTL not yet supported, running single instead");
                break;
            default:
                require_once(dirname(__FILE__).'/processors/superfecta_single.php');
		$superfecta = NEW superfecta_single($db,$amp_conf,$astman,$debug,$thenumber_orig,$scheme_name,$scheme_param);
		$superfecta->type = 'SUPER';
                break;
        }
	$superfecta->setCLI($cli);
	$superfecta->DID = $DID;
        
	//We only want to run all of this if it's a parent-multifecta or the original code (single-fecta), No need to run this for every child
	if(($superfecta->isDebug()) && (($superfecta->type == 'SUPER') || (($superfecta->type == 'MULTI') && ($superfecta->multi_type == 'PARENT')))){
		// If debugging, report all errors
		if($superfecta->isDebug(3)){
			error_reporting(E_ALL | E_NOTICE); //strict is way too much information! :-( 
		} else {
			error_reporting(E_ALL); // -1 was not letting me see the wood for the trees.
		}
		ini_set('display_errors', '1');
		$superfecta->outn("<strong>Debug is on and set at level ".$superfecta->getDebug()."</strong>");
		$superfecta->outn("<strong>The Original Number: </strong>". $superfecta->thenumber_orig);
		$superfecta->outn("<strong>The Scheme: </strong>". $superfecta->scheme_name);
		$superfecta->outn("<strong>Scheme Type: </strong>".$superfecta->type."FECTA");
		$superfecta->outn("<strong>SPAM Destination: </strong>".$scheme_param['spam_destination']);
		$superfecta->out("<strong>is CLI: </strong>");
		$superfecta->outn($cli ? 'true' : 'false');
		$start_time_whole = $superfecta->mctime_float();
		$end_time_whole = 0;
		$superfecta->outn("<b>Debugging Enabled, will not stop after first result.");
		$superfecta->outn("Scheme Variables:</b><pre>". print_r($superfecta->scheme_param,TRUE) . "</pre>");
	}
	//$superfecta->thenumber = ereg_replace('[^0-9]+', '', $superfecta->thenumber_orig);
	$superfecta->set_thenumber( preg_replace('/^\+[1-9]/','',$superfecta->thenumber_orig) );
	$superfecta->set_CurlTimeout( $scheme_param['Curl_Timeout']);

	$run_this_scheme = true;

	//We only want to run all of this if it's a parent-multifecta or the original code (single-fecta), No need to run this for every child
	if(($superfecta->type == 'SUPER') || (($superfecta->type == 'MULTI') && ($superfecta->multi_type == 'PARENT'))) {
		// Determine if this is the correct DID, if this scheme is limited to a DID.
		$rule_match = $superfecta->match_pattern_all( (isset($scheme_param['DID'])) ? $scheme_param['DID'] : '', $superfecta->DID );
		if($rule_match['number']){
			if($superfecta->isDebug()){$superfecta->outn("Matched DID Rule: '".$rule_match['pattern']."' with '".$rule_match['number']."'");}
		}elseif($rule_match['status']){
			if($superfecta->isDebug()){$superfecta->outn("No matching DID rules.");}
			$run_this_scheme = false;
		}

		// Determine if the CID matches any patterns defined for this scheme
		$rule_match = $superfecta->match_pattern_all((isset($scheme_param['CID_rules']))?$scheme_param['CID_rules']:'', $superfecta->get_thenumber() );
		if($rule_match['number'] && $run_this_scheme){
			if($superfecta->isDebug()){$superfecta->outn("Matched CID Rule: '".$rule_match['pattern']."' with '".$rule_match['number']."'");}
			$superfecta->set_thenumber( $rule_match['number'] );
		}elseif($rule_match['status'] && $run_this_scheme){
			if($superfecta->isDebug()){$superfecta->outn("No matching CID rules.");}
			$run_this_scheme = false;
		}

		//if a prefix lookup is enabled, look it up, and truncate the result to 10 characters
		///Clean these up, set NULL values instead of blanks then don't check for ''
		$superfecta->set_Prefix('');
		if((isset($scheme_param['Prefix_URL'])) && (trim($scheme_param['Prefix_URL']) != ''))
		{
			if($superfecta->isDebug())
			{
				$start_time = $superfecta->mctime_float();
			}
	
			$superfecta->set_Prefix( $superfecta->get_url_contents(str_replace("[thenumber]",$superfecta->get_thenumber(),$scheme_param['Prefix_URL'])));

			if($superfecta->isDebug())
			{
				$superfecta->outn("Prefix Url defined ...");
				if($superfecta->prefix !='')
				{
					$superfecta->outn("returned: ".$superfecta->get_Prefix());
				}
				else
				{
					$superfecta->outn("result was empty");
				}
				$superfecta->outn("result <img src='images/scrollup.gif'> took ".number_format((mctime_float()-$start_time),4)." seconds.");
			}
		}

		if($run_this_scheme) {
			if(!$cli) {
				$callerid = $superfecta->web_debug();				
			} else {
				$callerid = $superfecta->get_results();			
			}
				
			if ($callerid !='')
			{
				//$first_caller_id = _utf8_decode($first_caller_id);
				$callerid = strip_tags($callerid);
				$callerid = trim ($callerid);
				if ($superfecta->isCharSetIA5())
				{
					$callerid = $superfecta->stripAccents($callerid);
				}
				$callerid = preg_replace ( "/[\";']/", "", $callerid);
				//limit caller id to the first 60 char
				$callerid = substr($callerid,0,60);
			}
	
			$superfecta->send_results($callerid);
			
			$spam_text = ($superfecta->isSpam()) ? $scheme_param['SPAM_Text'] : '';

			$spam_dest = (!empty($scheme_param['spam_interceptor']) && ($scheme_param['spam_interceptor'] == 'Y')) ? $scheme_param['spam_destination'] : '';
			$spam_dest = ($superfecta->get_SpamCount() >= $scheme_param['SPAM_threshold']) ? $spam_dest : '';
			if(!$superfecta->isDebug()) {
				if($cli) {
					if($callerid != '') {
					    $final_data['cid'] = $spam_text." ".$superfecta->get_Prefix().$callerid;
					    $final_data['destination'] = $spam_dest;
					    echo serialize($final_data);
                                            //This takes us out of the loop so that we don't get multiple returned results like: AndrewNAGY,ANDREWAndrewWIRELESS CALLER
					    break;
					}
				} else {
                                        //We are still web-bing it up, just don't want any crap to be shown. so lets only show the scheme
					echo $scheme_name.": ".$spam_text." ".$superfecta->get_Prefix().$callerid."<br/>\n";
				}
			} else {
				if(!empty($spam_dest)) {
				    $superfecta->outn("<b>SPAM Call sent to:</b> ".$spam_dest);
				}
				$superfecta->out("<b>Returned Result would be: ");
				$callerid = utf8_encode($spam_text." ".$superfecta->get_Prefix().$callerid);
				$superfecta->outn($callerid);
				$end_time_whole = ($end_time_whole == 0) ? $superfecta->mctime_float() : $end_time_whole;
				$superfecta->outn("result <img src='images/scrollup.gif'> took ".number_format(($end_time_whole-$start_time_whole),4)." seconds.</b>");
                                $superfecta->outn("<hr>");
			}
		}
	} elseif(($superfecta->type == 'MULTI') && ($superfecta->multi_type == 'CHILD')) {
		if(!$cli) {
			$callerid = $superfecta->web_debug();				
		} else {
			$callerid = $superfecta->get_results();			
		}
	}
}

function FnDeprecated($fnName) { die("<strong>Error - </strong>Function <strong>{$fnName}</strong> is deprecated."); }
 
function cisf_find_area ($area_array, $full_number) { FnDeprecated(__FUNCTION__); }

function cisf_url_encode_array($arr) { FnDeprecated(__FUNCTION__); }

function get_url_contents($url,$post_data=false,$referrer=false,$cookie_file=false,$useragent=false) { FnDeprecated(__FUNCTION__); }

function mctime_float() { FnDeprecated(__FUNCTION__); }

function match_pattern_all($array, $number) { FnDeprecated(__FUNCTION__); }

function match_pattern($pattern, $number) { FnDeprecated(__FUNCTION__); }

function stripAccents($string) { FnDeprecated(__FUNCTION__); }

function isutf8($string) { FnDeprecated(__FUNCTION__); }

function _utf8_decode($string) { FnDeprecated(__FUNCTION__); }