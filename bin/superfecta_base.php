<?php
class superfecta_base {
	public $debug = FALSE;
	public $thenumber;
	public $db; //The database
	
	function post_processing($cache_found,$winning_source,$first_caller_id,$run_param,$thenumber) {
		return($thenumber);
	}

	function get_caller_id($thenumber,$run_param=array()) {
		//Is this the best way to do this?
		$caller_id = NULL; 
		return($caller_id);
	}

	function settings() {
		//Is this the best way to do this?
		$settings = array(); 
		return($settings);
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
}