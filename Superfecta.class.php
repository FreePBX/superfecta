<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 POSSA Working Group
//
namespace FreePBX\modules;
class Superfecta implements \BMO {
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

  public function ajaxRequest($req, &$setting) {
    switch($req) {
      case "power":
      case "delete":
      case "options":
      case "save_options":
      case "update_sources":
        return true;
      break;
    }
  }

  public function ajaxHandler() {
    switch($_REQUEST['command']) {
      case "update_sources":
        $sources = implode(",", $_REQUEST['data']);
        $sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES(?, ?, 'sources')";
        $sth = $this->db->prepare($sql);
        $sth->execute(array($sources, $_REQUEST['scheme']));
        return array("success" => true);
      break;
      case "power":
        $data = preg_replace('/^scheme_/i', '', $_REQUEST['scheme']);
        $sql = "UPDATE superfectaconfig SET value = (value * -1) WHERE field = 'order' AND source = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute(array($data));
        return array("status" => true);
      break;
      case "delete":
        $data = preg_replace('/^scheme_/i', '', $_REQUEST['scheme']);
        $sql = "DELETE FROM superfectaconfig WHERE source = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute(array($data));

        //We now have to reorder the array. Well, we don't -have- to. But it's prettier
        $sql = "SELECT * FROM superfectaconfig WHERE field LIKE 'order' ORDER BY value ASC";
        $sth = $this->db->prepare($sql);
        $sth->execute();
        $scheme_list = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $order = 1;
        foreach($scheme_list as $data) {
          $sql = "REPLACE INTO superfectaconfig (value, source, field) VALUES(?, ?, 'order')";
          $sth = $this->db->prepare($sql);
          $sth->execute(array($order, $data['source']));
          $order++;
        }
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
            $sth->execute(array($scheme . "_" . $source, $key, $_POST[$key]));
          } else {
            $sql = "DELETE FROM superfectaconfig WHERE source = ? AND field = ?";
            $sth = $this->db->prepare($sql);
            $sth->execute(array($scheme . "_" . $source, $key));
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

        $form_html = '<form id="form_options_'.$_REQUEST['source'].'" action="ajax.php?module=superfecta&command=save_options&scheme='.$_REQUEST['scheme'].'&source='.$_REQUEST['source'].'" method="post">';
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

  public function getAllSchemes() {
    $sql = "SELECT source as scheme, value as powered FROM superfectaconfig WHERE source LIKE 'base_%' AND field = 'order' ORDER BY ABS(value)";
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

  public function getScheme($scheme) {
    //set some default values for creating a new scheme
    //TODO This is weird
    if ($scheme == 'new') {
      $return = array(
        'Curl_Timeout' => 3,
        'SPAM_Text' => 'SPAM'
      );
    } else {
      $sql = "SELECT field, value FROM superfectaconfig WHERE source = ?";
      $sth = $this->db->prepare($sql);
      $sth->execute(array($scheme));
      $return = $sth->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    if (!isset($return['multifecta_timeout'])) {
      $return['multifecta_timeout'] = '1.5';
    }
    if (!isset($return['enable_multifecta'])) {
      $return['enable_multifecta'] = '';
    }
    if (!isset($return['SPAM_threshold'])) {
      $return['SPAM_threshold'] = '3';
    }

    $return['sources'] = !empty($return['sources']) ? explode(',', $return['sources']) : array();

    return $return;
  }
}
