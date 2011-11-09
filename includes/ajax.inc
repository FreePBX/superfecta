<?php
require("superfecta_base.php");
$superfecta = new superfecta_base;
//DB_FETCHMODE_ASSOC
include('JSON/JSON.php');

$array['success'] = false;

$root_dir = dirname(dirname(__FILE__));

switch($_REQUEST['type']) {
    case 'gen_list':
        $sql = "SELECT value FROM superfectaconfig WHERE source = '".$_REQUEST['scheme']."' AND field = 'sources'";
        $data = sql($sql, 'getOne');
        if($data) {
            $sources = explode(',',$data);
            foreach($sources as $key => $data) {
                if(file_exists($root_dir.'/sources/source-'.$data.'.module')) {
                    require($root_dir.'/sources/source-'.$data.'.module');
                    
                }
            }
            $array['success'] = true;
        }
        break;
}

print_r($array);
echo json_encode($array);