#!/usr/bin/php -q
<?php
if(php_sapi_name() != 'cli' && !empty($_SERVER['REMOTE_ADDR'])) {
	die('This must be run from the command line interface (CLI)');
}
echo "This script will generate & update the source-list.xml file which is used for the superfecta online updating system inside the module\n\n";
require("bin/superfecta_base.php");
$superfecta = new superfecta_base;
$old_source_list_xml = $superfecta->xml2array("bin/source-list.xml");
$i = 0;
$source_list_array = array();
foreach (glob("bin/*.module") as $filename) {
	$path_parts = pathinfo($filename);
	$class_name = str_replace(".".$path_parts['extension'],"",$path_parts['basename']);
	$class_name = str_replace("source-","",$class_name);
	require($filename);

	$class = new $class_name;

	if(!method_exists($class,'settings')) {
		echo "No Settings function in class: ".$class_name."...skipping\n";
		continue;
	}
	
	$settings = $class->settings();
	
	
	$md5_sum = md5_file($filename);
	
	echo "Parsing source: ". $class_name."\n";
	
	$location = $superfecta->arraysearchrecursive($path_parts['basename'],$old_source_list_xml,'name');
	//echo $old_source_list_xml['data']['source'][$location[2]]['md5'];
	if($location) {
		if($old_source_list_xml['data']['source'][$location[2]]['md5'] != $md5_sum) {
			echo "\t".$class_name." has changed since last time this script ran\n";
			$source_list_array[$i]['name'] = $path_parts['basename'];
			$source_list_array[$i]['modified'] = time();
			$source_list_array[$i]['md5'] = $md5_sum;
			$source_list_array[$i]['version_requirement'] = (isset($settings['version_requirement'])) ? $settings['version_requirement'] : 'NULL';
		} else {
			echo "\t".$class_name." has not changed since last time this script ran\n";
			$source_list_array[$i]['name'] = $old_source_list_xml['data']['source'][$location[2]]['name'];
			$source_list_array[$i]['modified'] = $old_source_list_xml['data']['source'][$location[2]]['modified'];
			$source_list_array[$i]['md5'] = $old_source_list_xml['data']['source'][$location[2]]['md5'];
			$source_list_array[$i]['version_requirement'] = $old_source_list_xml['data']['source'][$location[2]]['version_requirement'];
		}
	} else {
		echo "\t".$class_name." is new and has not been added since last time script ran\n";
		$source_list_array[$i]['name'] = $path_parts['basename'];
		$source_list_array[$i]['modified'] = time();
		$source_list_array[$i]['md5'] = $md5_sum;
		$source_list_array[$i]['version_requirement'] = (isset($settings['version_requirement'])) ? $settings['version_requirement'] : 'NULL';
	}
	$i++;
}

$final_xml = "<data>\n";
foreach($source_list_array as $data) {
	$final_xml .= "\t<source>\n";
	
	$final_xml .= "\t\t<name>".$data['name']."</name>\n";
	$final_xml .= "\t\t<modified>".$data['modified']."</modified>\n";
	$final_xml .= "\t\t<md5>".$data['md5']."</md5>\n";
	$final_xml .= "\t\t<version_requirement>".$data['version_requirement']."</version_requirement>\n";
	
	$final_xml .= "\t</source>\n";
}
$final_xml .= "</data>";

file_put_contents('bin/source-list.xml',$final_xml);