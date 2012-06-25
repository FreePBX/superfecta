<?php
require_once("superfecta_base.php");
$superfecta = new superfecta_base;
require_once('JSON/JSON.php');

$root_dir = dirname(dirname(__FILE__));

$out = array("success" => false);

switch($_REQUEST['type']) {
    case "display_debug":
        echo "ok";
        die();
        break;
    case "update_sources":
        $sources_list = json_decode($_REQUEST['data']);
        $sources_commaed = implode(",", $sources_list);
        $sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES('".mysql_real_escape_string($sources_commaed)."', '".mysql_real_escape_string($_REQUEST['scheme'])."', 'sources')";
        sql($sql);   
        $out = array("success" => true);
        break;
    case "update_schemes":
        $scheme_list = json_decode($_REQUEST['data']);
        $order = 1;
        foreach($scheme_list as $data) {
            $data = preg_replace('/^scheme_/i', '', $data);
            $sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES('".mysql_real_escape_string($order)."', '".mysql_real_escape_string($data)."', 'order')";
            sql($sql);
            $order++;
        }
        $out = array("success" => true);
        break;
    case "update_scheme":
        $enable_interceptor = mysql_real_escape_string(utf8_decode($_POST['enable_interceptor']));
        $enable_interceptor = (isset($enable_interceptor) && $enable_interceptor == 'Y') ? TRUE : FALSE;
	$scheme_name = mysql_real_escape_string(preg_replace('/\s/i', '_', preg_replace('/\+/i', '_', trim($_POST['scheme_name']))));
	$scheme_name_orig = mysql_real_escape_string($_POST['scheme_name_orig']);
	$DID = mysql_real_escape_string($_POST['DID']);
	$CID_rules = mysql_real_escape_string($_POST['CID_rules']);
	$Prefix_URL = mysql_real_escape_string($_POST['Prefix_URL']);
	$Curl_Timeout = mysql_real_escape_string($_POST['Curl_Timeout']);
	$http_password = mysql_real_escape_string(utf8_decode($_POST['http_password']));
	$http_username = mysql_real_escape_string(utf8_decode($_POST['http_username']));
	$SPAM_Text = mysql_real_escape_string($_POST['SPAM_Text']);
	$SPAM_Text_Substitute = (isset($_POST['SPAM_Text_Substitute'])) ? mysql_real_escape_string($_POST['SPAM_Text_Substitute']) : 'N';
	$processor =  mysql_real_escape_string(utf8_decode($_POST['processor']));
	$multifecta_timeout =  mysql_real_escape_string(utf8_decode($_POST['multifecta_timeout']));
	$SPAM_threshold = mysql_real_escape_string($_POST['SPAM_threshold']);
	$error = false;

        $type = mysql_real_escape_string($_POST['goto0']);
	$destination = mysql_real_escape_string($_POST[$type.'0']);

	//see if the scheme name has changed, and make sure that there isn't already one named the new name.
	if($scheme_name == "")
	{
		$error = true;
		print '<p><strong>Scheme names cannot be blank.</strong></p>';
	}

	if(($scheme_name != $scheme_name_orig) && !$error)
	{
		$sql = "SELECT * FROM superfectaconfig WHERE source='base_".$scheme_name."'";
		$results = sql($sql, "getAll");

		if(!empty($results))
		{
			$error = true;
			print '<p><strong>You cannot rename a scheme the same thing as an existing scheme.</strong></p>';
		}
		else
		{
			$sql = "UPDATE superfectaconfig SET source = 'base_".$scheme_name."' WHERE source = 'base_".$scheme_name_orig."'";
			sql($sql);
			
			$sql = "UPDATE superfecta_to_incoming SET scheme = 'base_".$scheme_name."' WHERE scheme = 'base_".$scheme_name_orig."'";
			sql($sql);
		}
	}

	if(!$error)
	{
		//update database
                if($enable_interceptor) {
                	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','spam_interceptor','Y')";
                } else {
                	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','spam_interceptor','N')";
                }
                sql($sql);
            	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','spam_destination','$destination')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','Prefix_URL','$Prefix_URL')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','Curl_Timeout','$Curl_Timeout')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','processor','$processor')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','multifecta_timeout','$multifecta_timeout')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_Text','$SPAM_Text')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_Text_Substitute','$SPAM_Text_Substitute')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','DID','$DID')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','CID_rules','$CID_rules')";
		sql($sql);
		$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_".$scheme_name."','SPAM_threshold','$SPAM_threshold')";
		sql($sql);
		print '<p><strong>CID Scheme Updated</strong></p>';
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
    case "delete_scheme":
        $data = preg_replace('/^scheme_/i', '', $_REQUEST['scheme']);
        $sql = "DELETE FROM superfectaconfig WHERE source = '".  mysql_real_escape_string($data)."'";
        sql($sql);
        
        //We now have to reorder the array. Well, we don't -have- to. But it's prettier
        $sql = "SELECT * FROM superfectaconfig WHERE field LIKE 'order' ORDER BY value ASC";
        $scheme_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
        $order = 1;
        foreach($scheme_list as $data) {
            $sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES('".mysql_real_escape_string($order)."', '".mysql_real_escape_string($data['source'])."', 'order')";
            sql($sql);
            $order++;
        }
        
        $out = array("success" => true);
        break;
    case "power_scheme":
        $data = preg_replace('/^scheme_/i', '', $_REQUEST['scheme']);
        $sql = "UPDATE superfectaconfig SET value = (value * -1) WHERE field = 'order' AND source = '".  mysql_real_escape_string($data)."'";
        sql($sql);
        break;
    case "options":
        $show = FALSE;
        $scheme = str_replace("base_", "", $_REQUEST['scheme']);
        
        $source = $_REQUEST['source'];
                
        $sql = "SELECT * FROM superfectaconfig WHERE source = '".$scheme . "_" . $source."'";
        $settings = sql($sql, 'getAll');
        
        foreach($settings as $data) {
            $n_settings[$data[1]] = $data[2];
        }
                
        $path = dirname(dirname(__FILE__));
        require_once($path.'/sources/source-'.$_REQUEST['source'].'.module');
        $module = new $_REQUEST['source'];
        $params = $module->settings();
        
        $title = str_replace('_', ' ', $_REQUEST['source']);
        $form_html = '<h3>'.$title.'</h3><form id="form_options_'.$_REQUEST['source'].'" action="config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=save_options&scheme='.$_REQUEST['scheme'].'&source='.$_REQUEST['source'].'" method="post">';
        $form_html .= '<table>';
        foreach($params['source_param'] as $key => $data) {
            $form_html .= '<tr>';
            $show = TRUE;
            $default = isset($data['default']) ? $data['default'] : '';
            switch($data['type']) {
                case "text":
                    $value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
                    $form_html .= '<td>'.str_replace("_", " ", $key).'</td><td><input type="text" name="'.$key.'" value="'.$value.'" /></td>';
                    break;
                case "password":
                    $value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
                    $form_html .= '<td>'.str_replace("_", " ", $key).'</td><td><input type="password" name="'.$key.'" value="'.$value.'" /></td>';
                    break;
                case "checkbox":
                    $checked = isset($n_settings[$key]) && ($n_settings[$key] == 'on') ? 'checked' : $default; 
                    $form_html .= '<td>'.str_replace("_", " ", $key).'</td><td><input type="checkbox" name="'.$key.'" value="on" '.$checked.'/></td>';
                    break;
                case "textarea":
                    $value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
                    $form_html .= '<td>'.str_replace("_", " ", $key).'</td><td><textarea name="'.$key.'">'.$value.'</textarea></td>';
                    break;
                case "number":
                    $value = isset($n_settings[$key]) ? $n_settings[$key] : $default;
                    $form_html .= '<td>'.str_replace("_", " ", $key).'</td><td><input type="number" name="'.$key.'" value="'.$value.'" /></td>';
                    break;
                default:
                    $form_html .= '<td colspan="2"><span class="superfecta_message">WARN: Unrecognized option \''.$data['type'].'\'</span></td>';
                    break;
            }
            $form_html .= '</tr>';
        }
        $form_html .= '</table>';
        $form_html .= '<input type="submit" value="Submit" /></form>';

        $out = array("success" => true, "show" => $show, "data" => $form_html);
        break;
    case "save_options":
        file_put_contents('/var/www/html/admin/modules/superfecta/log', print_r($_REQUEST,TRUE));
        
        $path = dirname(dirname(__FILE__));
        require_once($path.'/sources/source-'.$_REQUEST['source'].'.module');
        $module = new $_REQUEST['source'];
        $params = $module->settings();
        
        $scheme = str_replace("base_", "", $_REQUEST['scheme']);
        $source = $_REQUEST['source'];
               
        foreach($params['source_param'] as $key => $data) {
            if(isset($_REQUEST[$key])) {
                $sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES ('".$scheme . "_" . $source."', '".$key."', '".$_REQUEST[$key]."')";
                sql($sql);
            } else {
                $sql = "DELETE FROM superfectaconfig WHERE source = '".$scheme . "_" . $source."' AND field = '".$key."'";
                sql($sql);
            }
        }
        break;
}

echo json_encode($out);