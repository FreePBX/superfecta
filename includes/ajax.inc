<?php
require_once("superfecta_base.php");
$superfecta = new superfecta_base;
require_once('JSON/JSON.php');

$root_dir = dirname(dirname(__FILE__));

$out = array("success" => false);

switch($_REQUEST['type']) {
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