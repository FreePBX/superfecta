<?php

class superfecta_multi extends superfecta_base {
	function __construct($multifecta_id,$db,$amp_conf,$debug,$thenumber_orig,$scheme_name,$scheme_param,$source) {
		//Check if we are a multifecta child, if so, get our variables from our child record
		$this->multifecta_id = $multifecta_id;
		$this->debug = $debug;
		$sn = explode("_", $scheme_name);
		$this->scheme_name = $sn[1];
		$this->scheme = $scheme_name;
		$this->db = $db;
		$this->amp_conf = $amp_conf;
		$this->thenumber_orig = $thenumber_orig;
		$this->scheme_param = $scheme_param;
		$this->source = $source;
		$this->path_location = str_replace("includes/processors","sources",dirname(__FILE__));
		if($multifecta_id){
			$this->multi_type = 'CHILD';
		} else {
			$this->multi_type = 'PARENT';
		}
		if($this->multi_type == "CHILD"){
			$query  = "SELECT mf.superfecta_mf_id, mf.scheme, mf.cidnum, mf.extension, mf.debug, mfc.source
					FROM superfecta_mf mf, superfecta_mf_child mfc
					WHERE mfc.superfecta_mf_child_id = " . $this->db->quoteSmart($this->multifecta_id) . "
					AND mf.superfecta_mf_id = mfc.superfecta_mf_id";

			$res = $this->db->query($query);
			if (DB::IsError($res)){
				die("Unable to load child record: " . $res->getMessage() .  "<br>");
			}
			if($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){

				$this->scheme = $row['scheme'];
				$this->thenumber_orig = $row['cidnum'];
				$this->DID = $row['extension'];
				$this->multifecta_parent_id = $row['superfecta_mf_id'];
				if($row['debug']){
					$this->debug = true;
				}
				$this->single_source = $row['source'];
			}else{
				die("Unable to load multifecta child record '".$this->multifecta_id."'");
			}
		}
	}
	
	function get_results() {
		if($this->multi_type == 'PARENT') {
			return($this->run_parent());
		} elseif ($this->multi_type = 'CHILD') {
			$this->run_child();
		}
	}
	
	function run_parent() {
		// We are a multifecta parent
		$multifecta_start_time = $this->mctime_float();
		// Clean up multifecta records that are over 10 minutes old
		$query = "DELETE mf, mfc FROM superfecta_mf mf, superfecta_mf_child mfc
				WHERE mf.timestamp_start < ".$this->db->quoteSmart($multifecta_start_time - (60*10))."
				AND mfc.superfecta_mf_id = mf.superfecta_mf_id
				";
		$res2 = $this->db->query($query);
		if (DB::IsError($res2)){
			die("Unable to delete old multifecta records: " . $res2->getMessage() .  "<br>");
		}

		// Prepare for launching children.
		$query = "INSERT INTO superfecta_mf (
				timestamp_start, 
				scheme, 
				cidnum, 
				extension, 
				prefix, 
				debug
			) VALUES (
				".$this->db->quoteSmart($multifecta_start_time).",
				".$this->db->quoteSmart($this->scheme).",
				".$this->db->quoteSmart($this->thenumber_orig).",
				".$this->db->quoteSmart('NULL').",
				".$this->db->quoteSmart('PREFIX').",
				".$this->db->quoteSmart(($this->debug)?'1':'0')."
			)";
		// Create the parent record
		$res2 = $this->db->query($query);
		if (DB::IsError($res2)){
			die("Unable to create parent record: " . $res2->getMessage() .  "<br>");
		}
		// (jkiel - 01/04/2011) Get id of the parent record 
		// (jkiel - 01/04/2011) [Insert complaints on Pear DB not supporting a last_insert_id method here]
		// (jkiel - 01/04/2011) What is the point of an abstraction layer when you are forced to bypass it?!?!?
		if($superfecta_mf_id = (($this->amp_conf["AMPDBENGINE"] == "sqlite3") ? sqlite_last_insert_rowid($this->db->connection) : mysql_insert_id($this->db->connection)))
		{
			// We have the parent record id
			if($this->debug) {
				$this->outn("Multifecta Parent ID:".$superfecta_mf_id."<br>\n");
			}
		}else{
			die("Unable to get parent record id<br>");
		}
		$sources = explode(",",$this->scheme_param['sources']);
		$multifecta_count = 1;
		foreach($sources as $data) {
			$multifecta_child_start_time = $this->mctime_float();
			$query = "INSERT INTO superfecta_mf_child (
						superfecta_mf_id,
						priority,
						source,
						timestamp_start
					) VALUES (
						".$this->db->quoteSmart($superfecta_mf_id).",
						".$this->db->quoteSmart($multifecta_count).",
						".$this->db->quoteSmart($data).",
						".$this->db->quoteSmart($multifecta_child_start_time)."
					)";
			// Create the child record
			$res2 = $this->db->query($query);
			if (DB::IsError($res2)){
				die("Unable to create child record: " . $res2->getMessage() .  "<br>");
			}
			if($superfecta_mf_child_id = (($this->amp_conf["AMPDBENGINE"] == "sqlite3") ? sqlite_last_insert_rowid($this->db->connection) : mysql_insert_id($this->db->connection))){
				if($this->debug){
					$this->outn("Spawning child ".$superfecta_mf_child_id.":". $data);
					exec('/usr/bin/php /var/www/html/admin/modules/superfecta/includes/callerid.php -s '.$this->scheme_name.' -n '.$this->thenumber_orig.' -m ' . $superfecta_mf_child_id . ' -r '.$data.' > log-'.$superfecta_mf_child_id.'.log 2>&1 &');
				} else {
					exec('/usr/bin/php /var/www/html/admin/modules/superfecta/includes/callerid.php -s '.$this->scheme_name.' -n '.$this->thenumber_orig.' -d -m ' . $superfecta_mf_child_id . ' -r '.$data.' > /dev/null 2>&1 &');
					
				}
			}
			$multifecta_count++;
		}
		if($this->debug){
			$this->outn("Parent took ".number_format(($this->mctime_float()-$multifecta_start_time),4)." seconds to spawn children.<br>\n");
		}
		$query = "SELECT superfecta_mf_child_id, priority, cnam, spam_text, spam, source, cached
				FROM superfecta_mf_child
				WHERE superfecta_mf_id = ".$this->db->quoteSmart($superfecta_mf_id)."
				AND timestamp_cnam IS NOT NULL
				ORDER BY priority
				";
		$loop_limit = 200; // Loop 200 times maximum, just incase our timeout function fails
		$loop_start_time = $this->mctime_float();
		$loop_cur_time = $this->mctime_float();
		$loop_priority_time_limit = $this->scheme_param['multifecta_timeout'];
		$loop_time_limit = ($this->scheme_param['Curl_Timeout'] + .5); //Give us an extra half second over CURL
		$multifecta_timeout_hit = false;
		while($loop_limit && (($loop_cur_time - $loop_start_time)<=$loop_time_limit)){
			$res2 = $this->db->query($query);
			if (DB::IsError($res2)){
				die("Unable to search for winning child: " . $res2->getMessage() .  "<br>");
			}
			$winning_child_id = false;
			$last_priority = 0;
			$first_caller_id = '';
			$spam_text = '';
			$spam = '';
			$spam_source = '';
			$spam_child_id = false;
			$loop_cur_time = $this->mctime_float();
			while($res2 && ($row2 = $res2->fetchRow(DB_FETCHMODE_ASSOC))){
				/*** FUTURE
				echo "<pre>";
				print_r($row2);
				echo "</pre>";
				if($row2['cnam'] && (!$first_caller_id)){
					$first_caller_id = $row2['cnam'];
					$winning_child_id = $row2['superfecta_mf_child_id'];
					$winning_source = $row2['source'];
					$cache_found = $row2['cached'];
					break;
				}
				**/
				// Wait for a winning child, in the order of it's preference
				// Take the first to finish after multifecta_timeout is reached
				if(($row2['priority']==$last_priority) 
					|| ($loop_limit == 1) 
					|| (($loop_cur_time - $loop_start_time)>$loop_time_limit)
					|| (($loop_cur_time - $loop_start_time)>$loop_priority_time_limit)
				){
					if((!$multifecta_timeout_hit) && (($loop_cur_time - $loop_start_time)>$loop_priority_time_limit)){
						$multifecta_timeout_hit = true;
						if($this->debug){
							$this->outn("Multifecta Timeout reached.  Taking first child with a CNAM result.");
						}
					}
					// Record the results of any spam sources
					// We dont break out of the loop for spam though.  We'll just keep
					// checking it over and over until we get a cnam or we time-out.
					$spam_text = (($row2['spam_text'])?$row2['spam_text']:$spam_text);
					if($row2['spam']){
						$this->spam = TRUE;
						$spam_text = $row2['spam_text'];
						$spam_source = $row2['source'];
						$spam_child_id = $row2['superfecta_mf_child_id'];
					}
					// If we hit a cnam result, we are done.  break out of the loop.
					$spam = (($row2['spam_text'])?$row2['spam']:$spam);
					if($row2['cnam'] && (!$first_caller_id)){
						$first_caller_id = $row2['cnam'];
						$winning_child_id = $row2['superfecta_mf_child_id'];
						$winning_source = $row2['source'];
						$cache_found = $row2['cached'];
						break;
					}
					$last_priority ++;
				}
			}
			// We have a cnam, break out of this loop too
			if($first_caller_id){ break; }
			$loop_limit --;
			if($loop_limit && ($loop_cur_time - $loop_start_time)<=$loop_time_limit){
				usleep(50000); // sleep for 1/20 second. Short delay, but should help from taxing the system too much.
			}else{
				if($this->debug){
					$this->outn("Maximum timeout reached.  Will not wait for any more children.");
					break;
				}
			}
		}			
		
		if($this->debug) {			
			$sql = 'SELECT superfecta_mf_child_id, source FROM superfecta_mf_child WHERE superfecta_mf_id = '.$superfecta_mf_id;
			$list =& $this->db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
			usleep(50000);
			foreach ($list as $data) {
					echo "<b>Debug From Child-".$data['superfecta_mf_child_id']."-".$data['source'].":</b><br/>";
					echo "<pre>";
					echo file_get_contents("log-".$data['superfecta_mf_child_id'].".log");
					echo "</pre>";
					unlink("log-".$data['superfecta_mf_child_id'].".log");
			}
		}
		
		$multifecta_parent_end_time = $this->mctime_float();
		$query = "UPDATE superfecta_mf
			SET timestamp_end = ".$this->db->quoteSmart($multifecta_parent_end_time);
			if($winning_child_id){
				$query .= ",
				winning_child_id = ".$this->db->quoteSmart($winning_child_id);
			}
			if($spam_child_id){
				$query .= ",
				spam_child_id = ".$this->db->quoteSmart($spam_child_id);
			}
			$query .= "
			  	WHERE superfecta_mf_id = ".$this->db->quoteSmart($superfecta_mf_id)."
				";
		$res2 = $this->db->query($query);
					
		if($this->debug && $loop_cur_time){
			$this->outn("Parent waited " . number_format(($loop_cur_time - $loop_start_time),4) . " seconds for children's results.");
		}
		if($this->debug && $first_caller_id){
			$this->outn("Winning CNAM child source ".$winning_child_id.":". $winning_source.", with: ".$first_caller_id);
		}
		if($this->debug && $spam_text){
			$this->outn("Winning SPAM child source ".$spam_child_id.":". $spam_source);
		}
		if($this->debug && (!$first_caller_id) && (!$spam_text)){
			$this->outn("No winning SPAM or CNAM children found in allotted time.");
			return(FALSE);
		}	
		return($first_caller_id);		
	}
	
	function run_child() {
		$start_time = $this->mctime_float();
		
		$sql = "SELECT field,value FROM superfectaconfig WHERE source = '".$this->scheme_name."_".$this->source."'";
		$run_param = $this->db->getAssoc($sql);
        
		print_r($run_param);
		
		$source_file = $this->path_location."/source-".$this->source.".module";
		
		if(file_exists($source_file)) {
			require_once($source_file);
			$source_class = NEW $this->source;
			//Gotta be a better way to do this
			$source_class->debug = $this->debug;
			$source_class->amp_conf = $this->amp_conf;
			$source_class->db = $this->db;
	
			if(method_exists($source_class, 'get_caller_id')) {
				$caller_id = $source_class->get_caller_id($this->thenumber,$run_param);
				$this->spam = $source_class->spam;
				$this->cache_found = $source_class->cache_found;
				unset($source_class);
				$caller_id = $this->_utf8_decode($caller_id);

				if(isset($this->multifecta_id)) {
					$this->caller_id_array[$this->multifecta_id] = $caller_id;
				}
				if(($this->first_caller_id == '') && ($caller_id != ''))
				{
					if($this->debug)
					{
						echo "<br/>Returned Result was: ".$caller_id;
					}
				}
				$end_time_whole = $this->mctime_float();
				
				$multifecta_child_cname_time = $this->mctime_float();
				$query = "UPDATE superfecta_mf_child
						SET timestamp_cnam = ".$this->db->quoteSmart($multifecta_child_cname_time);
						if($caller_id){
							$query .= ",
							cnam = ".$this->db->quoteSmart(trim($this->caller_id_array[$this->multifecta_id]));
						}
						if($this->spam_text){
							$query .= ",
							spam_text = ".$this->db->quoteSmart($this->spam_text);
						}
						if($this->spam){
							$query .= ",
							spam = ".$this->db->quoteSmart($this->spam);
						}
						if($this->cache_found){
							$query .= ",
							cached = 1";
						}
						$query .= ", timestamp_end = ". $end_time_whole ."
					  	WHERE superfecta_mf_child_id = ".$this->db->quoteSmart($this->multifecta_id)."
						";
				$res = $this->db->query($query);
				if (DB::IsError($res)){
					die("Unable to update child: " . $res->getMessage() .  "<br>");
				}
			} elseif($this->debug) {
				print "Function 'get_caller_id' does not exist!<br>\n";
			}
		} elseif($this->debug) {
			print "Unable to find source '".$this->source."' skipping..<br\>\n";
		}
	}
	
	function send_results($caller_id) {

		$sources = explode(",",$this->scheme_param['sources']);
		
		if($this->debug)
		{
			$this->outn("Post CID retrieval processing.");
		}	
		foreach($sources as $source_name)
		{
			// Run the source
			$sql = "SELECT field,value FROM superfectaconfig WHERE source = '".$this->scheme_name."_".$data."'";
			$run_param = $this->db->getAssoc($sql);
			
			if(file_exists("source-".$source_name.".module")) {
				require_once("source-".$source_name.".module");
				$source_class = NEW $source_name;
				$source_class->db = $this->db;
				$source_class->debug = $this->debug;
				if(method_exists($source_class, 'post_processing')) {					
					$caller_id = $source_class->post_processing(FALSE,NULL,$caller_id,$run_param,$this->thenumber_orig);
				} else {
					print "Method 'post_processing' doesn't exist<br\>\n"; 
				}
			}
		}
	}
	
	//Run this when web debug is initiated
	function web_debug() {
		return($this->get_results());
	}
}