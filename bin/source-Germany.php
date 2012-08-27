<?php
//This file searches german Sources for Entries based on the given DID
//V1.0, first developed 2012/05/02 by Julian Franken and Chris Bischoff

$source_desc = "German Sources including business and residental data";

$source_param = array();
$source_param['Search_Source']['desc'] = 'Select which german sources you want to search';
$source_param['Search_Source']['type'] = 'select';
$source_param['Search_Source']['option'][1] = 'All';
$source_param['Search_Source']['option'][2] = 'Klicktel';
$source_param['Search_Source']['option'][3] = 'DeTeMedien';
$source_param['Search_Source']['option'][4] = 'Infobel DE';
$source_param['Search_Source']['default'] = 1;
$source_param['Result_Preference']['desc'] = 'Select your Preference how the Results will be used if possible';
$source_param['Result_Preference']['type'] = 'select';
$source_param['Result_Preference']['option'][1] = 'Shortest first';
$source_param['Result_Preference']['option'][2] = 'Klicktel first';
$source_param['Result_Preference']['option'][3] = 'DeTeMedien first';
$source_param['Result_Preference']['option'][4] = 'Infobel first';
$source_param['Result_Preference']['default'] = 1;
$source_param['Mark_as_external_found']['desc'] = 'If you wish to mark the Result as an external found Entry use your sign in the Textbox (e.g. EXT)';
$source_param['Mark_as_external_found']['type'] = 'text';
$source_param['Mark_as_external_found']['default'] = '';

//run this if the script is running in the "get caller id" usage mode.

if($usage_mode == 'get caller id')
{


	if($run_param['Search_Source'] == 1)
	{
		if($debug)
		{
			print 'Searching in all german Sources...<br> ';
		}

	      	$urlklicktel = "http://www.klicktel.de/inverssuche/index/search?method=search&_dvform_posted=1&phoneNumber=". $thenumber;
		$resultklicktel =  get_url_contents($urlklicktel);
		preg_match_all('/.html"><strong>(.*)<\/strong>/',$resultklicktel,$sklicktel);
		if (count($sklicktel[0]) > 0)
		{
			$sklicktel = $sklicktel[1][0];			
		}
		else
		{
			$sklicktel="";
		}
		if ($sklicktel != "")
		{
			$sklicktel = strip_tags($sklicktel);			
		}
		if($debug)
		{
			print 'Klicktel: ' .$sklicktel. '<br>';
		}

		$urldtm = "http://www.dasoertliche.de/Controller?context=4&form_name=search_inv&action=43&page=5&ph=". $thenumber;
		$resultdtm =  get_url_contents($urldtm);
		preg_match_all('/arkey=.*>(.*)&nbsp;/',$resultdtm,$sdtm);
		if (count($sdtm[0]) > 0)
		{
			$sdtm = $sdtm[1][0];			
		}
		else
		{
			$sdtm="";
		}
		if ($sdtm != "")
		{
			$sdtm = strip_tags($sdtm);			
		}
		if($debug)
		{
			print 'DeTeMedien: ' .$sdtm. '<br>';
		}


		$urlib = "http://www.infobel.com/de/germany/Inverse.aspx?q=germany&qPhone=". $thenumber;
		$resultib =  get_url_contents($urlib);
		preg_match_all('/<span class="fn org">(.*)<\/span><\/a>/',$resultib,$sib);
		if (count($sib[0]) > 0)
		{
			$sib = $sib[1][0];			
		}
		else
		{
			$sib="";
		}
		if ($sib != "")
		{
			$sib = strip_tags($sib);			
		}
		if($debug)
		{
			print 'Infobel DE: ' .$sib. '<br>';
		}

		if (strlen($sklicktel.$sdtm.$sib) > 0)
		{
			if($run_param['Result_Preference'] == 2)
			{
				if (strlen($sklicktel) > 0)
				{
					$daresult = $sklicktel;
				}
				else
				{
					if ((strcmp($sdtm,$sib)) < 0 AND (strlen($sib)) > 0)
					{
						$daresult = $sib;
					}
					else
					{
						$daresult = $sdtm;
					}
				}
			}

			if($run_param['Result_Preference'] == 3)
			{
				if (strlen($sdtm) > 0)
				{
					$daresult = $sdtm;
				}
				else
				{
					if ((strcmp($sklicktel,$sib)) < 0 AND (strlen($sib)) > 0)
					{
						$daresult = $sib;
					}
					else
					{
						$daresult = $sklicktel;
					}
				}
			}

			if($run_param['Result_Preference'] == 4)
			{
				if (strlen($sib) > 0)
				{
					$daresult = $sib;
				}
				else
				{
					if ((strcmp($sdtm,$sklicktel)) < 0 AND (strlen($sklicktel)) > 0)
					{
						$daresult = $sklicktel;
					}
					else
					{
						$daresult = $sdtm;
					}
				}
			}

			if($run_param['Result_Preference'] == 1)
			{
				if (strcmp($sdtm,$sklicktel) > 0)
				{

					if (strcmp($sdtm,$sib) > 0)
					{
						if (strlen($sdtm) > 0)
						{
							$daresult = $sdtm;
						}
						else
						{
							if (strcmp($sib,$sklicktel) > 0)
							{
								if (strlen($sib) > 0)
								{
									$daresult = $sib;
								}
								else
								{
									$daresult = $sklicktel;
								}
							}
						}
					}
					else
					{
						if (strlen($sib) > 0)
						{
							$daresult = $sib;
						}
						else
						{
							if (strcmp($sdtm,$sklicktel) > 0)
							{
								if (strlen($sdtm) > 0)
								{
									$daresult = $sdtm;
								}
								else
								{
									$daresult = $sklicktel;
								}
							}
						}

					}
				}
				else
				{

					if (strcmp($sklicktel,$sib) > 0)
					{

						if (strlen($sklicktel) > 0)
						{
							$daresult = $sklicktel;
						}
						else
						{
							if (strcmp($sib,$sdtm) > 0)
							{
								if (strlen($sib) > 0)
								{
									$daresult = $sib;
								}
								else
								{
									$daresult = $sdtm;
								}
							}
						}
					}
					else
					{
						if (strlen($sib) > 0)
						{
							$daresult = $sib;
						}
						else
						{
							if (strcmp($sdtm,$sklicktel) > 0)
							{
								if (strlen($sdtm) > 0)
								{
									$daresult = $sdtm;
								}
								else
								{
									$daresult = $sklicktel;
								}
							}
						}

					}
				}
			}

		}



		if ($daresult != "")
		{
			if($debug)
			{
				print 'Choosen result: ';
			}
			if (strlen($run_param['Mark_as_external_found']) > 0)
			{
				$caller_id = $run_param['Mark_as_external_found'] .' '. strip_tags($daresult);
			}
			else
			{
				$caller_id = strip_tags($daresult);
			}			
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}

	if($run_param['Search_Source'] == 2)
	{
		if($debug)
		{
			print 'Searching Klicktel...<br> ';
		}

	      	$urlklicktel = "http://www.klicktel.de/inverssuche/index/search?method=search&_dvform_posted=1&phoneNumber=". $thenumber;
		$resultklicktel =  get_url_contents($urlklicktel);
		preg_match_all('/.html"><strong>(.*)<\/strong>/',$resultklicktel,$sklicktel);
		if (count($sklicktel[0]) > 0)
		{
			$sklicktel = $sklicktel[1][0];			
		}
		else
		{
			$sklicktel="";
		}
		if ($sklicktel != "")
		{
			if (strlen($run_param['Mark_as_external_found']) > 0)
			{
				$caller_id = $run_param['Mark_as_external_found'] .' '. strip_tags($sklicktel);
			}
			else
			{
				$caller_id = strip_tags($sklicktel);
			}			
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}

	if($run_param['Search_Source'] == 3)
	{
		if($debug)
		{
			print 'Searching DeTeMedien...<br> ';
		}
		$urldtm = "http://www.dasoertliche.de/Controller?context=4&form_name=search_inv&action=43&page=5&ph=". $thenumber;
		$resultdtm =  get_url_contents($urldtm);
		preg_match_all('/arkey=.*>(.*)&nbsp;/',$resultdtm,$sdtm);
		if (count($sdtm[0]) > 0)
		{
			$sdtm = $sdtm[1][0];			
		}
		else
		{
			$sdtm="";
		}
		if ($sdtm != "")
		{
			if (strlen($run_param['Mark_as_external_found']) > 0)
			{
				$caller_id = $run_param['Mark_as_external_found'] .' '. strip_tags($sdtm);
			}
			else
			{
				$caller_id = strip_tags($sdtm);
			}			
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}

	if($run_param['Search_Source'] == 4)
	{
		if($debug)
		{
			print 'Searching Infobel Germany...<br> ';
		}
		$urlib = "http://www.infobel.com/de/germany/Inverse.aspx?q=germany&qPhone=". $thenumber;
		$resultib =  get_url_contents($urlib);
		preg_match_all('/<span class="fn org">(.*)<\/span><\/a>/',$resultib,$sib);
		if (count($sib[0]) > 0)
		{
			$sib = $sib[1][0];			
		}
		else
		{
			$sib="";
		}
		if ($sib != "")
		{
			if (strlen($run_param['Mark_as_external_found']) > 0)
			{
				$caller_id = $run_param['Mark_as_external_found'] .' '. strip_tags($sib);
			}
			else
			{
				$caller_id = strip_tags($sib);
			}			
		}
		else if($debug)
		{
			print "not found<br>\n";
		}
	}
}
