<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 POSSA Working Group
//
namespace FreePBX\modules;
class Superfecta implements \BMO {
	private $schemeDefaults = array(
		'Curl_Timeout' => 3,
		'SPAM_Text' => 'SPAM',
		'DID' => '',
		'CID_rules' => '',
		'processor' => 'superfecta_single.php',
		'multifecta_timeout' => '1.5',
		'Prefix_URL' => '',
		'SPAM_Text_Substitute' => false,
		'spam_interceptor' => false,
		'SPAM_threshold' => '3',
		'interceptor_select' => '',
		'sources' => array(),
		'name' => null
	);
	private $spamCount = 0;
	private $destination = null;
	private $agi = null;
	public function __construct($freepbx) {
		$this->freepbx = $freepbx;
		$this->db = $freepbx->Database;
	}
	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function doConfigPageInit($page){
		return true;
	}

	/**
	* Chown hook for freepbx fwconsole
	*/
	public function chownFreepbx() {
		$files = array();
		$files[] = array('type' => 'file',
												'path' => __DIR__."/agi/superfecta.agi",
												'perms' => 0755);
		return $files;
	}

	private function out($message) {
		if(is_object($this->agi)) {
			$this->agi->verbose($message);
		} elseif (php_sapi_name() != "cli") {
			echo "<span class='header'>".$message."</span><br/>";
		} else {
			echo $message."\n";
		}
	}

	public function setAgi($agi) {
		$this->agi = $agi;
	}
	public function execute($scheme='ALL', $request, $debug=0, $keepGoing=false) {
		if(empty($scheme) || !is_array($request) || empty($request)) {
			return '';
		}

		$trunk_info = array();

		foreach($request as $key => $value) {
			$key = preg_replace('/^agi_/','',$key);
			$trunk_info[$key] = $value;
		}

		$this->out(sprintf(_("Scheme Asked is: %s"),$scheme));
		$this->out(sprintf(_("The DID is: %s"),$trunk_info['extension']));
		$this->out(sprintf(_("The CNUM is: %s"),$trunk_info['callerid']));
		$this->out(sprintf(_("The CNAME is: %s"),$trunk_info['calleridname']));

		//If ALL then run through all Schemes, else just the single one
		if($scheme == 'ALL') {
			$schemes = $this->getAllPoweredSchemes();
		} else {
			$schemes[0] = array("name" => $scheme);
		}

		include __DIR__ . '/includes/superfecta_base.php';
		include __DIR__ . '/includes/processors/superfecta_multi.php';
		include __DIR__ . '/includes/processors/superfecta_single.php';



		global $db, $amp_conf, $astman;
		$options = array(
			'db' => $this->db,
			'amp_conf' => $amp_conf,
			'astman' => $astman,
			'debug' => 0,
			'path_location' => __DIR__ . '/sources',
			'trunk_info' => $trunk_info
		);

		foreach ($schemes as $s) {
			$this->out("");
			$this->out(sprintf(_("Starting scheme %s"),$s['name']));
			//reset these each time
			$cnum = $trunk_info['callerid'];
			$cnam = $trunk_info['calleridname'];
			$did = $trunk_info['extension'];

			$options['scheme_name'] = "base_".$s['name'];
			$options['scheme_settings'] = $this->getScheme($s['name']);;
			$options['module_parameters'] = $this->getSchemeAllModuleSettings($s['name']);

			switch ($options['scheme_settings']) {
				case 'superfecta_multi.php':
					//TODO: This is broken and needs to be fixed, there are better ways to do it of course
					//for now send all results back through to single
					//$options['multifecta_id'] = isset($multifecta_id) ? $multifecta_id : null;
					//$options['source'] = isset($source) ? $source : null;
					//$superfecta = NEW \superfecta_multi($options);
					//break;
				case 'superfecta_single.php':
				default:
					$superfecta = NEW \superfecta_single($options);
				break;
			}

			$superfecta->setDebug($debug);
			$superfecta->setCLI(true);
			$superfecta->setDID($did);
			$superfecta->set_CurlTimeout($options['scheme_settings']['Curl_Timeout']);

			// Determine if this is the correct DID, if this scheme is limited to a DID.
			$rule_match = $superfecta->match_pattern_all((isset($options['scheme_settings']['DID'])) ? $options['scheme_settings']['DID'] : '', $did);
			if ($rule_match['number']) {
				$this->out(sprintf(_("Matched DID Rule: %s with %s"),$rule_match['pattern'], $rule_match['number']));
			} elseif ($rule_match['status']) {
				$this->out(_("No matching DID rules. Skipping scheme"));
				continue;
			}

			// Determine if the CID matches any patterns defined for this scheme
			$rule_match = $superfecta->match_pattern_all((isset($options['scheme_settings']['CID_rules'])) ? $options['scheme_settings']['CID_rules'] : '', $cnum);
			if ($rule_match['number']) {
				$this->out(sprintf(_("Matched CID Rule: %s with %s"),$rule_match['pattern'], $rule_match['number']));
				$cnum = $rule_match['number'];
				$this->out(sprintf(_("Changed CNUM to: %s"),$cnum));
			} elseif ($rule_match['status']) {
				$this->out(_("No matching CID rules. Skipping scheme"));
				continue;
			}

			//if a prefix lookup is enabled, look it up, and truncate the result to 10 characters
			///Clean these up, set NULL values instead of blanks then don't check for ''
			$superfecta->set_Prefix('');
			if ((isset($scheme_param['Prefix_URL'])) && (trim($scheme_param['Prefix_URL']) != '')) {
				$start_time = $superfecta->mctime_float();

				$superfecta->set_Prefix($superfecta->get_url_contents(str_replace("[thenumber]", $cnum, $options['scheme_settings']['Prefix_URL'])));

				if ($superfecta->prefix != '') {
					$this->out(sprintf(_("Prefix Url defined as: %s"),$superfecta->get_Prefix()));
				} else {
					$this->out(_("Prefix Url defined but result was empty"));
				}
				$this->out(sprintf(_("Prefix Url result took %s seconds."),number_format((mctime_float() - $start_time), 4)));
			}

			$trunk_info['callerid'] = $cnum;
			$superfecta->set_TrunkInfo($trunk_info);

			if($this->agi === null) {
				$callerid = $superfecta->web_debug();
			} else {
				$callerid = $superfecta->get_results();
			}

			$callerid = trim($callerid);

			$found = false;
			if (!empty($callerid)) {
				$found = true;
				//$first_caller_id = _utf8_decode($first_caller_id);
				$callerid = trim(strip_tags($callerid));
				if ($superfecta->isCharSetIA5()) {
					$callerid = $superfecta->stripAccents($callerid);
				}
				//Why?
				$callerid = preg_replace("/[\";']/", "", $callerid);
				//limit caller id to the first 60 char
				$callerid = substr($callerid, 0, 60);
				
				// Display issues on phones and CDR with special characters
				// convert CNAM to UTF-8 to fix
				if (function_exists('mb_convert_encoding')) {
					$this->out("Converting result to UTF-8");
					$callerid = mb_convert_encoding($callerid, "UTF-8");
				}
				
				//send off
				$superfecta->send_results($callerid);
			}

			//Set Spam text
			$spam_text = ($superfecta->isSpam()) ? $options['scheme_settings']['SPAM_Text'] : '';
			if($superfecta->isSpam() && $options['scheme_settings']['SPAM_Text_Substitute'] == 'Y') {
				$callerid = $spam_text;
			} else {
				$callerid = $spam_text . " " . $superfecta->get_Prefix() . $callerid;
			}

			//Set Spam Destination
			$spam_dest = (!empty($options['scheme_settings']['spam_interceptor']) && ($options['scheme_settings']['spam_interceptor'] == 'Y')) ? $options['scheme_settings']['spam_destination'] : '';
			$this->spamCount = $this->spamCount + (int)$superfecta->get_SpamCount();
			if(!empty($spam_dest) && ($this->spamCount >= (int)$options['scheme_settings']['SPAM_threshold'])) {
				$parts = explode(",", $spam_dest);
				$this->destination = $parts;
				//stop all processing at this point, the spam score is too high
				if(!$keepGoing) {
					$this->out(sprintf(_("Spam Call, Sending call to: %s"),$spam_dest));
					return $callerid;
				} else {
					$this->out(sprintf(_("Call detected as spam, would send call to: %s"),$spam_dest));
				}
			}
			if(!empty($callerid)) {
				if(!$keepGoing) {
					$this->out(sprintf(_("Setting caller id to: %s"),$callerid));
					return $callerid;
				} else {
					$this->out(sprintf(_("This scheme would set the caller id to: %s"),$callerid));
				}
			} else {
				$this->out(_("No callerid found"));
			}
		}
		if(empty($callerid) && !$keepGoing) {
			//No callerid so I guess?
			return $trunk_info['calleridname'];
		} elseif(empty($callerid) && $keepGoing) {
			return false;
		} elseif(!empty($callerid) && $keepGoing) {
			return $callerid;
		}


	}

	public function getSpamScore() {
		return $this->spamCount;
	}

	public function getDest() {
		return $this->destination;
	}

	public function getSchemeDefaults() {
		$defaults = $this->schemeDefaults;
		$defaults['processors_list'] = $this->getProcessors();
		return $defaults;
	}

	public function getProcessors() {
		$processors_list = array();
		foreach (glob(__DIR__ . "/includes/processors/*.php") as $filename) {
			$name = explode("_", basename($filename));
			include $filename;
			$class_name = basename($filename, '.php');
			$class_class = new $class_name();
			$processors_list[] = array(
				"name" => strtoupper($class_class->name),
				"description" => $class_class->description,
				"filename" => basename($filename),
				"selected" => false, //functionality seems strange here
			);
			unset($class_class);
		}
		return $processors_list;
	}

	public function ajaxRequest($req, &$setting) {
		switch($req) {
			case "power":
			case "position":
			case "delete":
			case "options":
			case "save_options":
			case "update_sources":
			case "update_scheme":
			case "copy":
			case "sort":
			case "debug":
				return true;
			break;
		}
	}

	public function ajaxCustomHandler() {
		switch($_REQUEST['command']) {
			case "debug":
				echo "<span class='header'>"._('Debug is on and set at level:')."</span> ".$_REQUEST['level']."</br>";
				echo "<span class='header'>"._('The Original Number:')."</span> ".$_REQUEST['tel']."</br>";
				echo "<span class='header'>"._('The Scheme:')."</span> ".$_REQUEST['scheme']."</br>";
				echo "<span class='header'>"._('Scheme Type:')."</span> SINGLEFECTA</br>";
				echo "<span class='header'>"._('Debugging Enabled, will not stop after first result')."</span></br>";
				echo "</br>";
				$time_start = microtime(true);
				$callerid = $this->execute($_REQUEST['scheme'],array(
					'callerid' => $_REQUEST['tel'],
					'did' => '5555555555',
					'extension' => '5555555555',
					'calleridname' => 'CID Superfecta!',
				),$_REQUEST['level'],true);
				$time_end = microtime(true);
				echo "</br>";
				echo "<span class='header'>"._('Returned Result would be:')."</span>".$callerid."</br>";
				echo "<span class='header'>".sprintf(_('result took %s seconds'),$time_end - $time_start)."</span>";
				return true;
			break;
		}
	}

	public function ajaxHandler() {
		switch($_REQUEST['command']) {
			case "debug":
				return false;
			break;
			case "sort":
				$oper = $_POST['position'] == 'up' ? "-" : "+";
				$sql = "UPDATE superfectaconfig SET value = ABS(value ".$oper." 11) WHERE source = ? AND field = 'order'";
				$sth = $this->db->prepare($sql);
				$sth->execute(array('base_'.$_REQUEST['scheme']));
				$this->reorderSchemes();
				return array("status" => true);
			break;
			case "copy":
				$scheme = $_REQUEST['scheme'];
				$int = rand(1,10);

				/** GET SCHEME CONFIGS **/
				$sql = "SELECT * FROM superfectaconfig WHERE source = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array('base_'.$scheme));
				$options = $sth->fetchAll(\PDO::FETCH_ASSOC);
				$sql = "INSERT INTO superfectaconfig (source, field, value) VALUES (?,?,?)";
				$sth = $this->db->prepare($sql);
				foreach($options as $option) {
					$source = preg_replace('/^base_'.$scheme.'/','base_'.$scheme.'copy'.$int,$option['source']);
					$sth->execute(array($source,$option['field'],$option['value']));
				}

				/** GET MODULE CONFIGS **/
				$sql = "SELECT * FROM superfectaconfig WHERE source LIKE ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($scheme.'\_%'));
				$options = $sth->fetchAll(\PDO::FETCH_ASSOC);
				$sql = "INSERT INTO superfectaconfig (source, field, value) VALUES (?,?,?)";
				$sth = $this->db->prepare($sql);
				foreach($options as $option) {
					$source = preg_replace('/^'.$scheme.'_/','base_'.$scheme.'copy'.$int.'_',$option['source']);
					$sth->execute(array($source,$option['field'],$option['value']));
				}

				$this->reorderSchemes();
				return array("status" => true, "redirect" => "config.php?display=superfecta&action=edit&scheme=".$scheme.'copy'.$int);
			break;
			case "update_scheme":

				$data = array(
					"enable_interceptor" => $_POST['enable_interceptor'] == "on" ? TRUE : FALSE,
					"scheme_name" => preg_replace('/\s/i', '_', preg_replace('/\+/i', '_', trim($_POST['scheme_name']))),
					"scheme_name_orig" => $_POST['scheme_name_orig'],
					"DID" => $_POST['DID'],
					"CID_rules" => $_POST['CID_rules'],
					"Prefix_URL" => $_POST['Prefix_URL'],
					"Curl_Timeout" => $_POST['Curl_Timeout'],
					"SPAM_Text" => $_POST['SPAM_Text'],
					"SPAM_Text_Substitute" => $_POST['SPAM_Text_Substitute'] == "on" ? 'Y' : 'N',
					"processor" => utf8_decode($_POST['processor']),
					"multifecta_timeout" => utf8_decode($_POST['multifecta_timeout']),
					"SPAM_threshold" => $_POST['SPAM_threshold'],
				);

				$type = $_POST['goto0'];
				$data['destination'] = !empty($type) ? $_POST[$type.'0'] : '';

				//see if the scheme name has changed, and make sure that there isn't already one named the new name.
				if(empty($data['scheme_name'])) {
					return array("status" => false, "message" => _("Scheme names cannot be blank"));
				}

				$ret = $this->updateScheme($data['scheme_name_orig'],$data);
				if(!$ret['status']) {
					return array("status" => false, "message" => $ret['message']);
				}

				if($data['scheme_name'] != $data['scheme_name_orig']) {
					return array("status" => true, "redirect" => "config.php?display=superfecta&action=edit&scheme=".$data['scheme_name']);
				} else {
					return array("status" => true, "redirect" => "");
				}

				//add ordering information to database if this scheme doesn't have it
				$highest_order = 0;
				$already_has_order = false;
				$sql = "SELECT source,ABS(value) FROM superfectaconfig WHERE field = 'order' ORDER BY ABS(value)";
				$results = sql($sql, "getAll");
				foreach($results as $val) {
					if($val[0] == "base_".$scheme_name)
					{
						$already_has_order = true;
						break;
					}
					$highest_order = $val[1];
				}

				if(!$already_has_order) {
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','order',".($highest_order+1).")";
					sql($sql);
				}
			break;
			case "update_sources":
				$sources = isset($_REQUEST['data'])?implode(",", $_REQUEST['data']):'';
				$sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES(?, ?, 'sources')";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($sources, 'base_'.$_REQUEST['scheme']));
				return array("success" => true);
			break;
			case "power":
				$data = preg_replace('/^scheme_/i', '', $_REQUEST['scheme']);
				$sql = "UPDATE superfectaconfig SET value = (value * -1) WHERE field = 'order' AND source = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array('base_'.$data));
				return array("status" => true);
			break;
			case "delete":
				$sql = "DELETE FROM superfectaconfig WHERE source LIKE ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array('base_'.$_REQUEST['scheme']));
				$sth->execute(array('base_'.$_REQUEST['scheme'].'\_%'));
				$this->reorderSchemes();
				return array("status" => true);
			break;
			case "save_options":
				include(__DIR__."/includes/superfecta_base.php");
				$path = __DIR__;
				include $path.'/sources/source-'.$_REQUEST['source'].'.module';
				if(!class_exists($_REQUEST['source'])) {
					return array("status" => false);
				}
				$module = new $_REQUEST['source'];
				$params = $module->source_param;

				$scheme = $_REQUEST['scheme'];
				$source = $_REQUEST['source'];
				$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES (?, ?, ?)";
				$sth = $this->db->prepare($sql);
				foreach($params as $key => $data) {
                                        if (strcmp($data['type'], 'internal') != 0) {
					        $sth->execute(array($scheme . "_" . $source, $key, $_POST[$key]));
                                        }
				}
				return array("status" => true);
			break;
			case "options":
				include(__DIR__."/includes/superfecta_base.php");
				$scheme = $_REQUEST['scheme'];
				$source = $_REQUEST['source'];

				$sql = "SELECT field, value FROM superfectaconfig WHERE source = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($scheme . "_" . $source));
				$n_settings = $sth->fetchAll(\PDO::FETCH_KEY_PAIR);

				$path = __DIR__;

				include $path.'/sources/source-'.$_REQUEST['source'].'.module';
				if(!class_exists($_REQUEST['source'])) {
					return array("status" => false);
				}
				$module = new $_REQUEST['source'];
				$params = $module->source_param;

				$form_html = '<form id="form_options_'.$_REQUEST['source'].'" action="ajax.php?module=superfecta&command=save_options&scheme='.urlencode($scheme).'&source='.$source.'" method="post">';
				foreach($params as $key => $data) {
					$form_html .= '<div class="form-group">';
					$show = TRUE;
					$default = isset($data['default']) ? $data['default'] : '';
					switch($data['type']) {
						case "text":
							$value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
							$form_html .= '<label for="'.$key.'">'.str_replace("_", " ", $key).'</label><a class="info"><span>'.$data['description'].'</span></a>';
							$form_html .= '<input type="text" class="form-control" name="'.$key.'" id="'.$key.'" value="'.$value.'"/>';
						break;
						case "password":
							$value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
							$form_html .= '<label for="'.$key.'">'.str_replace("_", " ", $key).'</label><a class="info"><span>'.$data['description'].'</span></a>';
							$form_html .= '<input type="password" class="form-control" name="'.$key.'" id="'.$key.'" value="'.$value.'"/>';
						break;
						case "checkbox":
							$checked = $default;
							if(isset($n_settings[$key])) {
								if($n_settings[$key] == 'on') {
									$checked = 'checked';
								} else {
									$checked = '';
								}
							}
							$form_html .= '<label for="'.$key.'">'.str_replace("_", " ", $key).'</label><a class="info"><span>'.$data['description'].'</span></a>';
							$form_html .= '<br/><span class="radioset">';
							$form_html .= '<input type="radio" id="'.$key.'_yes" name="'.$key.'"value="on" '.($checked == 'checked' ? 'checked' : '').'>';
							$form_html .= '<label for="'.$key.'_yes">'._('Yes').'</label>';
							$form_html .= '<input type="radio" id="'.$key.'_no" name="'.$key.'"value="off" '.($checked != 'checked' ? 'checked' : '').'>';
							$form_html .= '<label for="'.$key.'_no">'._('No').'</label>';
							$form_html .= '</span>';
						break;
						case "textarea":
							$value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
							$form_html .= '<label  for="'.$key.'">'.str_replace("_", " ", $key).'</label><a class="info"><span>'.$data['description'].'</span></a>';
							$form_html .= '<textarea for="'.$key.'"area name="'.$key.'" class="form-control" rows="5" id="'.$key.'">'.$value.'</textarea>';
						break;
						case "number":
							$value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
							$form_html .= '<label for="'.$key.'">'.str_replace("_", " ", $key).'</label><a class="info"><span>'.$data['description'].'</span></a>';
							$form_html .= '<input type="number" class="form-control" name="'.$key.'" id="'.$key.'" value="'.$value.'" /></td>';
						break;
						case "info":
							$form_html .= $default;
						break;
						case "select":
							$value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
							$form_html .= '<label for="'.$key.'">'.str_replace("_", " ", $key).'</label><a class="info"><span>'.$data['description'].'</span></a>';
							$form_html .= '<select name="'.$key.'" class="form-control" id="'.$key.'">';
							foreach($data['option'] as $options_k => $options_l) {
								$selected = ($value == $options_k) ? 'selected' : '';
								$form_html .= "<option value=".$options_k." ".$selected.">".$options_l."</option>";
							}
							$form_html .= "</select>";
						break;
					}
					$form_html .= '</div>';
				}

				return array("status" => true, "title" => str_replace('_', ' ', $_REQUEST['source']), "html" => $form_html);
			break;
		}
	}

	public function reorderSchemes() {
		$sql = "SELECT * FROM superfectaconfig WHERE field = 'order' ORDER BY CONVERT(value, SIGNED INTEGER)";
		$start = 1;
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		foreach($results as $result) {
			$sql = "UPDATE superfectaconfig SET value = ? WHERE source = ? AND field = 'order'";
			$sth = $this->db->prepare($sql);
			$sth->execute(array($start*10, $result['source']));
			$start++;
		}

	}

	public function getAllPoweredSchemes() {
		$allSchemes = $this->getAllSchemes();
		$schemes = array();
		foreach($allSchemes as $scheme) {
			if($scheme['powered']) {
				$schemes[] = $scheme;
			}
		}
		return $schemes;
	}

	public function getAllSchemes() {
		$sql = "SELECT source as scheme, value as powered FROM superfectaconfig WHERE source LIKE 'base\_%' AND field = 'order' ORDER BY ABS(CONVERT(value, SIGNED INTEGER))";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);

		$i = 1;
		$scheme_list = array();
		$total = count($results);
		foreach ($results as $data) {
			$scheme_list[$i] = $data;
			$scheme_list[$i]['name'] = substr($data['scheme'], 5);
			$scheme_list[$i]['showdown'] = $i == $total ? FALSE : TRUE;
			$scheme_list[$i]['showup'] = $i == 1 ? FALSE : TRUE;
			$scheme_list[$i]['showdelete'] = TRUE;
			$scheme_list[$i]['powered'] = $data['powered'] < 0 ? FALSE : TRUE;
			$i++;
		}
		return $scheme_list;
	}

	public function addScheme($scheme, $data = array()) {
		if(empty($scheme)) {
			return array("status" => false, "message" => _("Scheme can not be empty!"));
		}
		$res = $this->getScheme($scheme);
		if(!empty($res)) {
			return array("status" => false, "message" => _("You cannot create a scheme the same name as an existing scheme"));
		}
		$data['order'] = 200;
		$data['scheme_name'] = $scheme;
		return $this->updateScheme($scheme,$data);
	}

	public function updateScheme($scheme, $data = array()) {
		if($data['scheme_name'] != $scheme)	{
			$res = $this->getScheme($data['scheme_name']);
			if(!empty($res)) {
				return array("status" => false, "message" => _("You cannot rename a scheme the same thing as an existing scheme"));
			} else {
				$sql = "UPDATE superfectaconfig SET source = REPLACE(source, ?, ?) WHERE source LIKE ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array('base_'.$scheme, 'base_'.$data['scheme_name'], 'base_'.$scheme));
				$sth->execute(array('base_'.$scheme.'_', 'base_'.$data['scheme_name'].'_', 'base_'.$scheme.'\_%'));

				$sql = "UPDATE superfecta_to_incoming SET scheme = ? WHERE scheme = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array('base_'.$data['scheme_name'], 'base_'.$scheme));

				$scheme = 'base_'.$data['scheme_name'];
			}
		} else {
			$scheme = 'base_'.$data['scheme_name'];
		}

		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES(?,?,?)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($scheme,'spam_interceptor',(!empty($data['enable_interceptor']) && $data['enable_interceptor'] == 'Y' ? 'Y' : 'N')));
		$sth->execute(array($scheme,'spam_destination',$data['destination']));
		$sth->execute(array($scheme,'Prefix_URL',$data['Prefix_URL']));
		$sth->execute(array($scheme,'Curl_Timeout',$data['Curl_Timeout']));
		$sth->execute(array($scheme,'processor',$data['processor']));
		$sth->execute(array($scheme,'multifecta_timeout',$data['multifecta_timeout']));
		$sth->execute(array($scheme,'SPAM_Text',$data['SPAM_Text']));
		$sth->execute(array($scheme,'SPAM_Text_Substitute',(!empty($data['SPAM_Text_Substitute']) && $data['SPAM_Text_Substitute'] == 'Y' ? 'Y' : 'N')));
		$sth->execute(array($scheme,'DID',$data['DID']));
		$sth->execute(array($scheme,'CID_rules',$data['CID_rules']));
		$sth->execute(array($scheme,'SPAM_threshold',$data['SPAM_threshold']));
		if(isset($data['order'])) {
			$sth->execute(array($scheme,'order',$data['order']));
		}
		return array("status" => true);
	}

	public function getScheme($scheme) {
		//strip off base if it's sent to us we dont need it
		$scheme = preg_replace('/^base_/','',$scheme);
		//set some default values for creating a new scheme
		$sql = "SELECT field, value FROM superfectaconfig WHERE source = ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array('base_'.$scheme));
		$return = $sth->fetchAll(\PDO::FETCH_KEY_PAIR);

		if(empty($return)) {
			return false;
		}

		foreach($this->schemeDefaults as $key => $value) {
			if($key == 'SPAM_Text_Substitute' || $key == 'spam_interceptor') {
				if(!isset($return[$key])) {
					$return[$key] = $key;
				} else {
					$return[$key] = ($return[$key] == 'Y') ? true : false;
				}
			} else {
				$return[$key] = !isset($return[$key]) ? $value : $return[$key];
			}
		}

		$return['name'] = $scheme;
		$return['sources'] = !empty($return['sources']) ? explode(',', $return['sources']) : array();
		return $return;
	}

	public function getSchemeAllModuleSettings($scheme) {
		$sql = "SELECT source, field, value FROM superfectaconfig WHERE source LIKE ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($scheme.'\_%'));
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$return = array();
		foreach($results as $result) {
			$result['source'] = preg_replace('/^'.$scheme.'_/i','',$result['source']);
			if(trim($result['value']) == 'off') {
				continue;
			}
			$return[$result['source']][$result['field']] = $result['value'];
		}
		return $return;
	}

	public function getSchemeModuleSettings($scheme, $module) {
		$sql = "SELECT source, field, value FROM superfectaconfig WHERE source LIKE ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($scheme.'\_'.$module.'\_%'));
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$return = array();
		foreach($results as $result) {
			$result['source'] = preg_replace('/^'.$scheme.'_/i','',$result['source']);
			if(trim($result['value']) == 'off') {
				continue;
			}
			$return[$result['source']][$result['field']] = $result['value'];
		}
		return $return;
	}

	public function getActionBar($request) {
		$buttons = array();
		$request['action'] = !empty($request['action']) ? $request['action'] : "";
		switch($request['action']) {
			case 'add':
				$buttons = array(
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
			break;
		}
		return $buttons;
	}
}
