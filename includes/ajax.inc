<?php
require("superfecta_base.php");
$superfecta = new superfecta_base;
include('JSON/JSON.php');


$root_dir = dirname(dirname(__FILE__));

$out = array("success" => false);

switch($_REQUEST['type']) {
    case "add":
        $sql = "SELECT value FROM superfectaconfig WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        
        $sources = sql($sql, "getOne");
        
        $sources = $sources . "," . $_REQUEST['source'];
        
        $sql = "UPDATE superfectaconfig SET value = '".$sources."' WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        sql($sql);
        
        $out = array("success" => true);
        break;
    case "remove":
        $sql = "SELECT value FROM superfectaconfig WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        
        $sources = sql($sql, "getOne");
        
        $sources = explode(",",$sources);
        
        $new = array();
        foreach($sources as $data) {
            if ($data != $_REQUEST['source']) {
                $new[] = $data;
            }
        }
        $sources = implode(",", $new);
        
        $sql = "UPDATE superfectaconfig SET value = '".$sources."' WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        sql($sql);
        
        $out = array("success" => true);
        
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
                        
        foreach($params['source_param'] as $key => $data) {
            $show = TRUE;
            switch($data['type']) {
                case "text":
                    $value = isset($n_settings[$key]) ? $n_settings[$key] : '';
                    $form_html .= '<label>'.str_replace("_", " ", $key).'<input type="text" name="'.$key.'" value="'.$value.'" /></label></br>';
                    break;
                case "password":
                    $value = isset($n_settings[$key]) ? $n_settings[$key] : '';
                    $form_html .= '<label>'.str_replace("_", " ", $key).'<input type="password" name="'.$key.'" value="'.$value.'" /></label></br>';
                    break;
                case "checkbox":
                    $checked = isset($n_settings[$key]) && ($n_settings[$key] == 'on') ? 'checked' : ''; 
                    $form_html .= '<label>'.str_replace("_", " ", $key).'<input type="checkbox" name="'.$key.'" value="on" '.$checked.'/></label></br>';
                    break;
            }
        }
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
    case "move":
        $sql = "SELECT value FROM superfectaconfig WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        
        $sources = sql($sql, "getOne");
                
        $sources = explode(",",$sources);
                
        $p = 0;
        foreach ($sources as $key => $data) {
            $p = $p + 10;
            switch ($_REQUEST['a']) {
                case "up":
                    if ($data == $_REQUEST['source']) {
                        $sources_o[$p - 15] = $_REQUEST['source'];
                    } else {
                        $sources_o[$p] = $data;
                    }
                    break;
                case "down":
                    if ($data == $_REQUEST['source']) {
                        $sources_o[$p + 15] = $_REQUEST['source'];
                    } else {
                        $sources_o[$p] = $data;
                    }
                    break;
            }
        }

        ksort($sources_o);
        
        $sources_o = implode(",",$sources_o);
        
        $sql = "UPDATE superfectaconfig SET value = '".$sources_o."' WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        sql($sql);
        
        $out = array("success" => true);
        break;
}

echo json_encode($out);