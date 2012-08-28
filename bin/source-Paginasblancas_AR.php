<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

// Last updated May 24, 2012

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Searches Argentina http://www.paginasblancas.com.ar/<br><br>This data source requires Superfecta Module version 2.2.4 or higher.";

//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	//  Initialize variables for use in this lookup source code
	$number_error = false;
	$name = "";
        $notfound = false;

	if($debug)
	{
		print "Searching http://www.paginasblancas.com.ar/ ... ";
	}

	//  Full list of Argentina area codes - taken from http://www.cnc.gov.ar/infotecnica/numeracion/indicativosinter.asp on December 17, 2010
	$npalist = array(
		"011","0220","02202","0221","02221","02223","02224","02225","02226","02227","02229",
		"0223","02241","02242","02243","02244","02245","02246","02252","02254","02255","02257",
		"02261","02262","02264","02265","02266","02271","02272","02273","02274","02281","02283",
		"02284","02285","02286","02291","02292","02293","02314","02316","02317","02320","02322",
		"02323","02324","02325","02326","02337","02342","02343","02344","02345","02346","02352",
		"02353","02354","02355","02356","02357","02358","02268","02296","02297","02362","02267",
		"0237","02392","02393","02394","02395","02396","02473","02474","02475","02477","02478",
		"0291","02921","02922","02923","02924","02925","02926","02927","02928","02929","02932",
		"02933","02935","02936","02982","02983","03327","03329","03382","03388","03407","03461",
		"03487","03488","03489","03832","03833","03835","03837","03838","03711","03715","03721",
		"03722","03725","03731","03732","03734","03735","03877","0297","02965","02945","02903",
		"03385","03387","02336","03472","03463","03467","03468","0351","03521","03522","03524",
		"03525","0353","03532","03533","03534","03541","03542","03543","03544","03546","03547",
		"03548","03549","03562","03563","03564","03571","03572","03573","03574","03575","03576",
		"0358","03582","03583","03585","03584","03756","03772","03773","03774","03775","03777",
		"03781","03782","03783","03786","0345","03454","03455","03456","03458","0343","03435",
		"03436","03437","03438","03442","03444","03445","03446","03447","03716","03718","03717",
		"0388","03884","03885","03886","03887","02941","02338","02333","02334","02335","02331",
		"02302","02952","02953","02954","03821","03822","03825","03826","03827","0261","02622",
		"02623","02624","02625","02626","02627","03741","03743","03758","03757","03755","03754",
		"03751","03752","02942","02948","0299","02972","02944","02946","02940","02934","02931",
		"02920","03878","03876","03868","0387","03875","0264","02646","02647","02648","02651",
		"02652","02658","02655","02656","02657","02902","02962","02963","02966","0342","03408",
		"03406","03409","0341","03400","03401","03402","03404","03405","03462","03460","03469",
		"03471","03464","03465","03466","03476","03482","03483","03491","03492","03493","03498",
		"03496","03497","03857","03858","03861","03844","03845","03846","0385","03854","03855",
		"03856","03841","03843","02964","02901","0381","03862","03863","03865","03867","03869",
		"03894","03891","03892",	);

/*  NPA Check disabled May 24, 2012 because NPA list is out of date
	// Check for supported npa
	$validnpa = cisf_find_area($npalist, $thenumber);
	if($validnpa===false)
	{
		$number_error = true;
	}
NPA Check disabled May 24, 2012 because NPA list is out of date */

	if (!$number_error)
		{
			$url="http://www.paginasblancas.com.ar/Telefono/".$thenumber;
			$value = get_url_contents($url);
			$notfound = strpos($value, "Su búsqueda no produjo ningún resultado");
			$notfound = ($notfound < 1) ? strpos($value, "Su búsqueda no produjo ningún resultado") : $notfound;

			if($notfound)
			{
				$name = "";
			}
			else
			{
				$begin = strpos($value, ">", strpos($value, 'advertise-name')) + 1;
				$end = strpos($value, "<", $begin);
				$name = trim(substr($value, $begin, $end-$begin));

			}

		}


		if(strlen($name) > 1)
		{
			$caller_id = trim(strip_tags($name));

		}
		else if($debug)
		{
			print "not found<br>\n";
		}



	if(($number_error) and ($debug))
	{
		print "Skipping Source - Not a valid or supported number: ".$thenumber."<br>\n";		
	}

}
