<?php

//	Superfecta code maintained by forummembers at PBXIAF.
//  Development SVN is at projects.colsolgrp.net
//	Caller ID Tricfecta / Superfecta was invented by Ward Mundy,
//  based on another authors work.
//
//	v 1.0.0 - 1.1.0 Created / coded by Tony Shiffer
//	V 2.0.0 - 2.20 Principle developer Jeremy Jacobs
//  v 2.2.1		Significant development by Patrick ELX
//
//	This program is free software; you can redistribute it and/or modify it
//	under the terms of the GNU General Public License as published by
//	the Free Software Foundation; either version 2 of the License, or
//	(at your option) any later version.
//

//Define our rootpath
define("SUPERFECTA_ROOT_PATH", dirname(__FILE__) . '/');

include(__DIR__."/includes/superfecta_base.php");

$module_info = FreePBX::Modules()->getInfo("superfecta");
$module_info = $module_info['superfecta'];

$scheme = (isset($_REQUEST['scheme'])) ? $_REQUEST['scheme'] : '';
$goto = NULL;

switch($action) {
  case "schemecopy":
    //determine the highest order amount.
    $query = "SELECT MAX(ABS(value)) FROM superfectaconfig WHERE field = 'order'";
    $results = sql($query, "getAll");
    $new_order = $results[0][0] + 1;

    //set new scheme name
    $name_good = false;
    $new_name = $schemecopy . ' copy';
    $new_name_count = 2;
    while (!$name_good) {
      $query = "SELECT * FROM superfectaconfig WHERE source = '" . $new_name . "'";
      $results = sql($query, "getAll");
      if (empty($results[0][0])) {
        $name_good = true;
      } else {
        if (substr($new_name, -4) == 'copy') {
          $new_name .= ' ' . $new_name_count;
        } else {
          $new_name = substr($new_name, 0, -2) . ' ' . $new_name_count;
        }
        $new_name_count++;
      }
    }

    //copy data from existing scheme into new scheme
    $query = "SELECT field,value FROM superfectaconfig WHERE source = '" . $schemecopy . "'";
    $results = sql($query, "getAll");
    foreach ($results as $val) {
      if (!empty($val)) {
        if ($val[0] == 'order') {
          $val[1] = $new_order;
        }
        $query = "REPLACE INTO superfectaconfig (source,field,value) VALUES('" . $new_name . "','$val[0]','$val[1]')";
        sql($query);
      }
    }

    $query = "SELECT source,field,value FROM superfectaconfig WHERE source LIKE '" . substr($schemecopy, 5) . "\\_%'";
    $results = sql($query, "getAll");
    foreach ($results as $val) {
      if (!empty($val)) {
        $new_name_source = substr($new_name, 5) . substr($val[0], strlen(substr($schemecopy, 5)));
        $query = "REPLACE INTO superfectaconfig (source,field,value) VALUES('$new_name_source','$val[1]','$val[2]')";
        sql($query);
      }
    }

    $scheme = $new_name;
  default:
}

$scheme_list = FreePBX::Superfecta()->getAllSchemes();
show_view(__DIR__."/views/header.php", array("schemes" => $scheme_list));

if ($scheme != "") {
    $conf = FreePBX::Superfecta()->getScheme($scheme);
    $goto = (!empty($conf['spam_destination'])) ? $conf['spam_destination'] : '';

    //Get list of processors
    $processors_list = array();
    $conf['processor'] = ((!isset($conf['processor'])) OR (empty($conf['processor']))) ? 'superfecta_single.php' : $conf['processor'];
    $processors_loc = dirname(__FILE__);
    foreach (glob($processors_loc . "/includes/processors/*.php") as $filename) {
        $name = explode("_", basename($filename));
        include $filename;
        $class_name = basename($filename, '.php');
        $class_class = new $class_name(); //PHP < 5.3
        $processors_list[] = array(
            "name" => strtoupper($class_class->name),
            "description" => $class_class->description,
            "filename" => basename($filename),
            "selected" => ($conf['processor'] == basename($filename)) ? TRUE : FALSE,
        );
        unset($class_class);
    }

    $displayvars = array(
      "scheme_name" => substr($scheme, 5),
      "did" => isset($conf['DID']) ? $conf['DID'] : '',
      "cid_rules" => isset($conf['CID_rules']) ? $conf['CID_rules'] : '',
      "curl_timeout" => isset($conf['Curl_Timeout']) ? $conf['Curl_Timeout'] : '5',
      "processors_list" => $processors_list,
      "multifecta_timeout" => isset($conf['multifecta_timeout']) ? $conf['multifecta_timeout'] : '1.5',
      "prefix_url" => isset($conf['Prefix_URL']) ? $conf['Prefix_URL'] : '',
      "spam_text" => isset($conf['SPAM_Text']) ? $conf['SPAM_Text'] : '',
      "spam_text_substitute" => isset($conf['SPAM_Text_Substitute']) && ($conf['SPAM_Text_Substitute'] == 'Y') ? TRUE : FALSE,
      "spam_int" => !empty($conf['spam_interceptor']) && ($conf['spam_interceptor'] == 'Y') ? TRUE : FALSE,
      "spam_threshold" => $conf['SPAM_threshold'],
      "interceptor_select" => drawselects($goto, 0, FALSE, FALSE)
    );

    define("ROOT_PATH", dirname(__FILE__) . '/');


    //get a list of the files that are on this local server
    $sources = array();
    $i = 0;
    foreach (glob(ROOT_PATH . "sources/source-*.module") as $filename) {
      if (file_exists($filename)) {
        $source_desc = '';
        $source_param = array();

        include $filename;

        preg_match('/source-(.*)\.module/i', $filename, $matches);
        $this_source_name = $matches[1];

        if(class_exists($this_source_name)) {
          $this_source_class = new $this_source_name();

          if (version_compare_freepbx($module_info['version'], $this_source_class->version_requirement, ">=")) {

            $j = !in_array($this_source_name, $conf['sources']) ? ($j = $i + 200) : ($j = $i);
            if (in_array($this_source_name, $conf['sources'])) {
              $j = array_search($this_source_name, $conf['sources']);
            } else {
              $j = $i + 200;
            }

            $sources[$j]['showup'] = FALSE;
            $sources[$j]['showdown'] = FALSE;
            $sources[$j]['pretty_source_name'] = str_replace("_", " ", $this_source_name);
            $sources[$j]['source_name'] = $this_source_name;
            $sources[$j]['enabled'] = in_array($this_source_name, $conf['sources']) ? TRUE : FALSE;
            $sources[$j]['status'] = in_array($this_source_name, $conf['sources']) ? 'enabled' : 'disabled';
            $sources[$j]['description'] = isset($this_source_class->description) ? preg_replace('/(<a>|<\/a>)/i', '', $this_source_class->description) : 'N/A';
            $sources[$j]['configure'] = isset($this_source_class->source_param) ? TRUE : FALSE;

            //Simplify please
            if (in_array($this_source_name, $conf['sources'])) {
              if ($conf['sources'][0] != $this_source_name) {
                $sources[$j]['showup'] = TRUE;
              }
              $c = count($conf['sources']) - 1;
              if ($conf['sources'][$c] != $this_source_name) {
                $sources[$j]['showdown'] = TRUE;
              }
            }
            $i++;
          }
        }
      }
    }

    ksort($sources);

    show_view(__DIR__.'/views/sources.php',array("sources" => $sources, "scheme" => substr($scheme, 5), "scheme_data" => $conf, "processors_list" => $processors_list));
}
