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

	public function myDialplanHooks() {
		return true;
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
				"selected" => ($conf['processor'] == basename($filename)) ? TRUE : FALSE,
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
				return true;
			break;
		}
	}

	public function ajaxHandler() {
		switch($_REQUEST['command']) {
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
				$sth->execute(array('base\_'.$scheme.'\_%'));
				$options = $sth->fetchAll(\PDO::FETCH_ASSOC);
				$sql = "INSERT INTO superfectaconfig (source, field, value) VALUES (?,?,?)";
				$sth = $this->db->prepare($sql);
				foreach($options as $option) {
					$source = preg_replace('/^base_'.$scheme.'_/','base_'.$scheme.'copy'.$int.'_',$option['source']);
					$sth->execute(array($source,$option['field'],$option['value']));
				}

				$this->reorderSchemes();
				return array("status" => true, "redirect" => "config.php?display=superfecta&action=edit&scheme=".$scheme.'copy'.$int);
			break;
			case "update_scheme":
				$data = array(
					"enable_interceptor" => (isset($_POST['enable_interceptor']) && $_POST['enable_interceptor'] == 'Y') ? TRUE : FALSE,
					"scheme_name" => preg_replace('/\s/i', '_', preg_replace('/\+/i', '_', trim($_POST['scheme_name']))),
					"scheme_name_orig" => $_POST['scheme_name_orig'],
					"DID" => $_POST['DID'],
					"CID_rules" => $_POST['CID_rules'],
					"Prefix_URL" => $_POST['Prefix_URL'],
					"Curl_Timeout" => $_POST['Curl_Timeout'],
					"SPAM_Text" => $_POST['SPAM_Text'],
					"SPAM_Text_Substitute" => (isset($_POST['SPAM_Text_Substitute'])) ? $_POST['SPAM_Text_Substitute'] : 'N',
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
				foreach($results as $val)
				{
					if($val[0] == "base_".$scheme_name)
					{
						$already_has_order = true;
						break;
					}
					$highest_order = $val[1];
				}

				if(!$already_has_order)
				{
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','order',".($highest_order+1).")";
					sql($sql);
				}
			break;
			case "update_sources":
				$sources = implode(",", $_REQUEST['data']);
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

				foreach($params as $key => $data) {
					if(isset($_POST[$key]) && $_POST[$key] != "off") {
						$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES (?, ?, ?)";
						$sth = $this->db->prepare($sql);
						$sth->execute(array('base_'.$scheme . "_" . $source, $key, $_POST[$key]));
					} else {
						$sql = "DELETE FROM superfectaconfig WHERE source = ? AND field = ?";
						$sth = $this->db->prepare($sql);
						$sth->execute(array('base_'.$scheme . "_" . $source, $key));
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
				$sth->execute(array('base_'.$scheme . "_" . $source));
				$n_settings = $sth->fetchAll(\PDO::FETCH_KEY_PAIR);

				$path = __DIR__;

				include $path.'/sources/source-'.$_REQUEST['source'].'.module';
				if(!class_exists($_REQUEST['source'])) {
					return array("status" => false);
				}
				$module = new $_REQUEST['source'];
				$params = $module->source_param;

				$form_html = '<form id="form_options_'.$_REQUEST['source'].'" action="ajax.php?module=superfecta&command=save_options&scheme='.$scheme.'&source='.$source.'" method="post">';
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
							$checked = isset($n_settings[$key]) && ($n_settings[$key] == 'on') ? 'checked' : $default;
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
		$sth->execute(array($scheme,'spam_interceptor',(!empty($data['enable_interceptor']) ? 'Y' : 'N')));
		$sth->execute(array($scheme,'spam_destination',$data['destination']));
		$sth->execute(array($scheme,'Prefix_URL',$data['Prefix_URL']));
		$sth->execute(array($scheme,'Curl_Timeout',$data['Curl_Timeout']));
		$sth->execute(array($scheme,'processor',$data['processor']));
		$sth->execute(array($scheme,'multifecta_timeout',$data['multifecta_timeout']));
		$sth->execute(array($scheme,'SPAM_Text',$data['SPAM_Text']));
		$sth->execute(array($scheme,'SPAM_Text_Substitute',(!empty($data['SPAM_Text_Substitute']) ? 'Y' : 'N')));
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
}
