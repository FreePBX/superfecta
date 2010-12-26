<?php
//from PiaF forum user harryhirch

$source_desc = "http://local.ch - 	These listings include business and residential data for Switzerland.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	if($debug)
	{
		print "Searching Local.ch-Swiss ... ";
	}

	$number_error = false;
	
	//check for the correct 11 digits Swiss phone numbers in international format.
	
	if (strlen($thenumber) == 10)
	{
		if (substr($thenumber,0,1) != '0')
		{
			$number_error = true;
		}
	}
	// country code + number
	if (strlen($thenumber) == 11)
	{
		if (substr($thenumber,0,2) == '41')
		{
			$thenumber = '0'.substr($thenumber,2);
		}
		else
		{
			$number_error = true;
		}

	}
	// international dialing prefix + country code + number
	if (strlen($thenumber) > 11)
	{
		if (substr($thenumber,0,4) == '0041')
		{
			$thenumber = '0'.substr($thenumber, 4);
		}
		else
		{
			if (substr($thenumber,0,5) == '01141')
			{
				$thenumber = '0'.substr($thenumber,5);
			}			
			else
			{
				$number_error = true;
			}
		}
	}	
	// number
       if(strlen($thenumber) < 10)
	{
		$number_error = true;
	}

	if(!$number_error)
	{
		$url="http://tel.search.ch/index.en.html?was=$thenumber";
		$value = get_url_contents($url);

		//////// open the website directory ///////////
		$AgetHeaders = @get_headers($url);
		if (preg_match("|200|", $AgetHeaders[0]))
		////////////////////////////////////////////// 
		{
			$fp = @fopen ($url,"r");
			while (!feof($fp))
			{
				$z = fgets($fp,5000);  // read etch line of the website

				if(ereg('class="rname"', $z)) 
				{
					$ik=explode('class="rname"',$z);
					$nk=$ik[1];
					$ik=explode('">',$nk);
					$nk=$ik[1];
					$ik=explode('<',$nk);
					$nk=$ik[0];
					$name=trim($nk);
				}
			} 	
			fclose($fp);
		}  

		if(strlen($name) > 1)
		{
			$caller_id = strip_tags($name);
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
	else
	{
		if($debug)
		{
			print "Skipping Source - Not a valid Swiss number: ".$thenumber."<br>\n";
		}
	}
}
?>