<?php

define("UPDATE_SERVER", "https://raw.github.com/tm1000/Caller-ID-Superfecta/v3.x/sources/");
define("ROOT_PATH", dirname(__FILE__).'/');

//fix in the future
$version = preg_replace('/(alpha|beta)/i', '.0.', $module_info['module']['version']);

//Check for updates if enabled
if($check_updates == 'on')
{
	$update_array = array();
	// Load files available on live update
	if(($check_updates == 'on') || ($update_file != ''))
	{
		$update_array = array();
		$source_list = $superfecta->xml2array(UPDATE_SERVER.'source-list.xml');
                
                if(array_key_exists('source', $source_list['data'])) {
                    foreach($source_list['data']['source'] as $sources) {
                            if($sources['version_requirement'] <= $version) {
                                $this_source_name = substr(substr(trim($sources['name']),7),0,-7);
                                $update_array[$this_source_name]['link'] = UPDATE_SERVER.$sources['name'];
                                $update_array[$this_source_name]['date'] = $sources['modified'];
                                $update_array[$this_source_name]['md5'] = $sources['md5'];
                                $update_array[$this_source_name]['version_requirement'] = $sources['version_requirement'];
                            } else {
                                $this_source_name = substr(substr(trim($sources['name']),7),0,-7);
                                $update_array[$this_source_name]['outdated'] = $sources['version_requirement'];
                            }
                    }
                } else {
                    $update_site_unavailable = true;
                    $check_updates = 'off';
                }
		
	}	
}

//Get the enabled sources from this scheme
$sql = "SELECT value FROM superfectaconfig WHERE source='$scheme' AND field='sources'";
$enabled_sources = explode(',',$db->getOne($sql));

//get a list of the files that are on this local server
$tpl_sources = array();
$i = 0;
foreach (glob(ROOT_PATH . "sources/source-*.module") as $filename) {
    if ($filename != '') {
        $source_desc = '';
        $source_param = array();

        require_once($filename);

        preg_match('/source-(.*)\.module/i', $filename, $matches);

        $this_source_name = $matches[1];
        $source_class = NEW $this_source_name;
        $settings = $source_class->settings();

        $j = !in_array($this_source_name, $enabled_sources) ? ($j = $i + 200) : ($j = $i);
        if(in_array($this_source_name, $enabled_sources)) {
            $j = array_search($this_source_name, $enabled_sources);
        } else {
            $j = $i + 200;
        }
        $tpl_sources[$j]['showup'] = FALSE;
        $tpl_sources[$j]['showdown'] = FALSE;
        $tpl_sources[$j]['showupdate'] = FALSE;
        $tpl_sources[$j]['filename'] = $filename;
        $tpl_sources[$j]['pretty_source_name'] = str_replace("_"," ", $this_source_name);
        $tpl_sources[$j]['source_name'] = $this_source_name;
        $tpl_sources[$j]['settings'] = $settings;
        $tpl_sources[$j]['enabled'] = in_array($this_source_name, $enabled_sources) ? TRUE : FALSE;
        $tpl_sources[$j]['status'] = in_array($this_source_name, $enabled_sources) ? 'enabled' : 'disabled';
        $tpl_sources[$j]['description'] = isset($settings['desc']) ? $settings['desc'] : 'N/A';
        $tpl_sources[$j]['show_link'] = isset($settings['source_param']) ? TRUE : FALSE;

        //Simplify please
        if(in_array($this_source_name, $enabled_sources)) {
            if($enabled_sources[0] != $this_source_name) {
                $tpl_sources[$j]['showup'] = TRUE;
            }
            $c = count($enabled_sources) - 1;
            if($enabled_sources[$c] != $this_source_name) {
                $tpl_sources[$j]['showdown'] = TRUE;
            }
        }

        $i++;
    }
}

ksort($tpl_sources);

$supertpl->assign("scheme", $scheme);

$supertpl->assign( "check_updates_check", ($check_updates == 'on') ? 'checked' : '' );

$supertpl->assign( "check_updates_check", ($update_site_unavailable) ? 'Update Server Unavalible' : '' );

$supertpl->assign( "sources" , $tpl_sources);

$supertpl->assign( "web_path" , 'http://'.$_SERVER['SERVER_NAME'].'/admin/modules/superfecta/tpl/js/jquery.form.js');

echo $supertpl->draw( 'sources' );                