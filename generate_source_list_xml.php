#!/usr/bin/php -q
<?php
if(php_sapi_name() != 'cli' && !empty($_SERVER['REMOTE_ADDR'])) {
	die('This must be run from the command line interface (CLI)');
}
echo "This script will generate & update the source-list.xml file which is used for the superfecta online updating system inside the module\n\n";
$old_source_list_xml = xml2array("bin/source-list.xml");
$i = 0;
$source_list_array = array();
foreach (glob("bin/source-*.php") as $filename) {
	$path_parts = pathinfo($filename);
	$source_name = str_replace(".".$path_parts['extension'],"",$path_parts['basename']);
	$source_name = str_replace("source-","",$source_name);	
	
	$md5_sum = md5_file($filename);
	
	echo "Parsing source: ". $source_name."\n";
	
	$location = arraysearchrecursive($path_parts['basename'],$old_source_list_xml,'name');
	//echo $old_source_list_xml['data']['source'][$location[2]]['md5'];
	if($location) {
		if($old_source_list_xml['data']['source'][$location[2]]['md5'] != $md5_sum) {
			echo "\t".$source_name." has changed since last time this script ran\n";
			$source_list_array[$i]['name'] = $path_parts['basename'];
			$source_list_array[$i]['modified'] = filemtime($filename);
			$source_list_array[$i]['md5'] = $md5_sum;
		} else {
			echo "\t".$source_name." has not changed since last time this script ran\n";
			$source_list_array[$i]['name'] = $old_source_list_xml['data']['source'][$location[2]]['name'];
			$source_list_array[$i]['modified'] = $old_source_list_xml['data']['source'][$location[2]]['modified'];
			$source_list_array[$i]['md5'] = $old_source_list_xml['data']['source'][$location[2]]['md5'];
		}
	} else {
		echo "\t".$source_name." is new and has not been added since last time script ran\n";
		$source_list_array[$i]['name'] = $path_parts['basename'];
		$source_list_array[$i]['modified'] = filemtime($filename);
		$source_list_array[$i]['md5'] = $md5_sum;
	}
	$i++;
}

$final_xml = "<data>\n";
foreach($source_list_array as $data) {
	$final_xml .= "\t<source>\n";
	
	$final_xml .= "\t\t<name>".$data['name']."</name>\n";
	$final_xml .= "\t\t<modified>".$data['modified']."</modified>\n";
	$final_xml .= "\t\t<md5>".$data['md5']."</md5>\n";
	
	$final_xml .= "\t</source>\n";
}
$final_xml .= "</data>";

file_put_contents('bin/source-list.xml',$final_xml);

/**
 * Taken from http://www.php.net/manual/en/function.array-search.php#69232
 * search haystack for needle and return an array of the key path, FALSE otherwise.
 * if NeedleKey is given, return only for this key mixed ArraySearchRecursive(mixed Needle,array Haystack[,NeedleKey[,bool Strict[,array Path]]])
 * @author ob (at) babcom (dot) biz
 * @param mixed $Needle
 * @param array $Haystack
 * @param mixed $NeedleKey
 * @param bool $Strict
 * @param array $Path
 * @return array
 */
function arraysearchrecursive($Needle,$Haystack,$NeedleKey="",$Strict=false,$Path=array()) {
    if(!is_array($Haystack))
        return false;
    foreach($Haystack as $Key => $Val) {
        if(is_array($Val)&&
                $SubPath=arraysearchrecursive($Needle,$Val,$NeedleKey,$Strict,$Path)) {
            $Path=array_merge($Path,Array($Key),$SubPath);
            return $Path;
        }
        elseif((!$Strict&&$Val==$Needle&&
                        $Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))||
                ($Strict&&$Val===$Needle&&
                        $Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))) {
            $Path[]=$Key;
            return $Path;
        }
    }
    return false;
}

/**
 * xml2array() will convert the given XML text to an array in the XML structure.
 * @author http://www.php.net/manual/en/function.xml-parse.php#87920
 * @param sting $url the XML url (usually a local file)
 * @param boolean $get_attributes 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
 * @param string $priority Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
 * @return array The parsed XML in an array form.
 */
function xml2array($url, $get_attributes = 1, $priority = 'tag') {
    $contents = "";
    if (!function_exists('xml_parser_create')) {
        return array ();
    }
    $parser = xml_parser_create('');
    if(!($fp = @ fopen($url, 'rb'))) {
        return array ();
    }
    while(!feof($fp)) {
        $contents .= fread($fp, 8192);
    }
    fclose($fp);
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if(!$xml_values) {
        return; //Hmm...
    }
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array ();
    foreach ($xml_values as $data) {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value)) {
            if($priority == 'tag') {
                $result = $value;
            }
            else {
                $result['value'] = $value;
            }
        }
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag') {
                    $attributes_data[$attr] = $val;
                }
                else {
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
        }
        if ($type == "open") {
            $parent[$level -1] = & $current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) {
                $current[$tag] = $result;
                if($attributes_data) {
                    $current[$tag . '_attr'] = $attributes_data;
                }
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else {
                if (isset ($current[$tag][0])) {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else {
                    $current[$tag] = array($current[$tag],$result);
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if(isset($current[$tag . '_attr'])) {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        else if($type == "complete") {
            if(!isset ($current[$tag])) {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if($priority == 'tag' and $attributes_data) {
                    $current[$tag . '_attr'] = $attributes_data;
                }
            }
            else {
                if (isset ($current[$tag][0]) and is_array($current[$tag])) {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else {
                    $current[$tag] = array($current[$tag],$result);
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes) {
                        if (isset ($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        else if($type == 'close') {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
}