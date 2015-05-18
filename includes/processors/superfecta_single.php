<?php

class superfecta_single extends superfecta_base {

	public $name = 'Single';
	public $description = 'Runs all sources in specified order, like old superfecta';
	public $type = 'SINGLE';
	private $winning = ''; //winning source, if any

	function __construct($options=array()) {
		if(!empty($options)) {
			$this->setDebug($options['debug']);
			$sn = explode("_", $options['scheme_name']);
			$this->scheme_name = $sn[1];
			$this->scheme = isset($options['scheme_name']) ? $options['scheme_name'] : '';
			$this->db = isset($options['db']) ? $options['db'] : '';
			$this->amp_conf = isset($options['amp_conf']) ? $options['amp_conf'] : '';
			$this->astman = isset($options['astman']) ? $options['astman'] : '';
			$this->scheme_params = isset($options['scheme_settings']) ? $options['scheme_settings'] : array();
			$this->source_params = isset($options['module_parameters']) ? $options['module_parameters'] : array();
			$this->path_location = isset($options['path_location']) ? $options['path_location'] : '';
			$this->trunk_info = isset($options['trunk_info']) ? $options['trunk_info'] : '';
		}
	}

	function is_master() {
		return(TRUE);
	}

	function get_results() {
		$caller_id = '';
		foreach ($this->scheme_params['sources'] as $data) {
			$this->caller_id = '';
			$start_time = $this->mctime_float();
			$run_params = !empty($this->source_params[$data]) ? $this->source_params[$data] : array();

			$source_name = $this->path_location . "/source-" . $data . ".module";
			$class = "\\".$data;
			if (file_exists($source_name) && !class_exists($class)) {
				include $source_name;
			}
			if(class_exists($class)) {
				$source_class = new $class;
				//Gotta be a better way to do this
				$source_class->setDebug($this->getDebug());
				$source_class->set_AmpConf($this->amp_conf);
				$source_class->set_DB($this->db);
				$source_class->set_AsteriskManager($this->astman);
				$source_class->set_TrunkInfo($this->trunk_info);

				if (method_exists($source_class, 'get_caller_id')) {
					if ($this->isDebug()) {
						print '<i class="fa fa-arrow-right"></i> '._('Executing'). ' '. str_replace("_"," ",$data).'<br/>';
					}
					$caller_id = $source_class->get_caller_id($this->trunk_info['callerid'], $run_params);
					$this->set_CacheFound($source_class->isCacheFound());
					$this->setSpam($source_class->isSpam());
					if ($source_class->isSpam()) {
						$this->set_SpamCount($this->get_SpamCount() + 1);
					}
					unset($source_class);
					$caller_id = $this->_utf8_decode($caller_id);


					if (($this->first_caller_id == '') && ($caller_id != '')) {
						$this->first_caller_id = $caller_id;
						$this->winning = $data;
						if ($this->isDebug()) {
							$end_time_whole = $this->mctime_float();
						}
                                                else { // Stop as soon as we have a valid answer
                                                    return($caller_id);
                                                }
					}
				}
			} else {
				$this->DebugPrint("Unable to find source '" . $source_name . "' skipping..");
			}

			if ($this->isDebug()) {
				if ($caller_id != '') {
					print "'" . utf8_encode($caller_id) . "'<br>\nresult <i class=\"fa fa-hand-o-up\"></i> took " . number_format(($this->mctime_float() - $start_time), 4) . " seconds.<br>\n<br>\n";
				} else {
					print "result <i class=\"fa fa-hand-o-up\"></i> took " . number_format(($this->mctime_float() - $start_time), 4) . " seconds.<br>\n<br>\n";
				}
			}
		}
		return($this->first_caller_id);
	}

	function send_results($caller_id) {
		if ($this->isDebug()) {
			print "<span class='header'>Post CID retrieval processing</span><br/>";
		}

		foreach ($this->scheme_params['sources'] as $source_name) {
			$run_param = !empty($this->source_params[$source_name]) ? $this->source_params[$source_name] : array();

			// Run the source
			$source_file = $this->path_location . "/source-" . $source_name . ".module";
			$class = "\\".$source_name;
			if (file_exists($source_name) && !class_exists($class)) {
				include $source_name;
			}
			if(class_exists($class)) {
				$source_class = new $class;
				//Gotta be a better way to do this
				$source_class->setDebug($this->getDebug());
				$source_class->set_AmpConf($this->amp_conf);
				$source_class->set_DB($this->db);
				$source_class->set_AsteriskManager($this->astman);
				$source_class->set_TrunkInfo($this->trunk_info);
				if (method_exists($source_class, 'post_processing')) {
					if ($this->isDebug()) {
						print '<i class="fa fa-arrow-right"></i> '._('Executing'). ' '. str_replace("_"," ",$source_name).'<br/>';
					}
					$source_class->post_processing($this->isCacheFound(), $this->winning, $caller_id, $run_param, $this->trunk_info['callerid']);
				}
			} else {
				$this->DebugPrint("Couldn't load " . $source_name . " for post processing");
			}
		}
		if ($this->isDebug()) {
			print "<span class='header'>Done</span><br/>";
		}
	}

	//Run this when web debug is initiated
	function web_debug() {
		return($this->get_results());
	}

}
