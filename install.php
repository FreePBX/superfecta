<?php
print 'Installing Caller ID Superfecta<br>';

if ((!function_exists('cidlookup_add'))||(!function_exists('cidlookup_edit'))) {
        die_freepbx( "CallerID Lookup not installed. Cannot install CallerID Superfecta.<br>\n");
}

//cleanup stray cidlookup_incoming records left by bad uninstalls
$sql = "delete c1 from cidlookup_incoming c1 left outer join cidlookup c2 on c1.cidlookup_id = c2.cidlookup_id where c2.cidlookup_id is null";
$check = $db->query($sql);

//a list of the columns that need to be included in the table. Functions below will add and delete columns as necessary.
$cols['source'] = "varchar(255) NOT NULL";
$cols['field'] = "varchar(255) NOT NULL";
$cols['value'] = "text default NULL";

// create the tables for options if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS superfectaconfig (";
foreach($cols as $key=>$val)
{
	$sql .= $key.' '.$val.', ';
}
$sql .= "PRIMARY KEY (source, field))";
$check = $db->query($sql);
if (DB::IsError($check))
{
	die_freepbx( "Can not create superfectaconfig table: ".$sql." - ".$check->getMessage() .  "<br>");
}

//create the cache table
$sql = "CREATE TABLE IF NOT EXISTS superfectacache (
				number BIGINT UNSIGNED NOT NULL,
				callerid VARCHAR(45) NOT NULL,
				dateentered DATETIME NOT NULL,
				PRIMARY KEY (number))
				ENGINE = MyISAM";
$check = $db->query($sql);
if (DB::IsError($check))
{
	die_freepbx( "Can not create superfectacache table: " . $check->getMessage() .  "<br>");
}

//check to see that the proper columns are in the table.
$curret_cols = array();
$sql = "DESC superfectaconfig";
$res = $db->query($sql);
while($row = $res->fetchRow())
{
	if(array_key_exists($row[0],$cols))
	{
		$curret_cols[] = $row[0];
		//make sure it has the latest definition
		$sql = "ALTER TABLE superfectaconfig MODIFY ".$row[0]." ".$cols[$row[0]];
		$check = $db->query($sql);
		if (DB::IsError($check))
		{
			die_freepbx( "Can not update column ".$row[0].": " . $check->getMessage() .  "<br>");
		}
	}
	else
	{
		//remove the column
		$sql = "ALTER TABLE superfectaconfig DROP COLUMN ".$row[0];
		$check = $db->query($sql);
		if(DB::IsError($check))
		{
			die_freepbx( "Can not remove column ".$row[0].": " . $check->getMessage() .  "<br>");
		}
		else
		{
			print 'Removed no longer needed column '.$row[0].' from superfectaconfig table.<br>';
		}
	}
}

//add columns that are not already in the table
foreach($cols as $key=>$val)
{
	if(!in_array($key,$curret_cols))
	{
		$sql = "ALTER TABLE superfectaconfig ADD ".$key." ".$val;
		$check = $db->query($sql);
		if (DB::IsError($check))
		{
			die_freepbx( "Can not add column ".$key.": " . $check->getMessage() .  "<br>");
		}
		else
		{
			print 'Added column '.$key.' to superfectaconfig table.<br>';
		}
	}
}

//move values from the old table into the new table is necessary
$sql = "SELECT * FROM superfectaoptions LIMIT 1;";
$res = $db->query($sql);
if(!DB::IsError($res))
{
	//since this an upgrade from the old version, it probably doesn't have the default values here
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Trunk_Provided','Ignore_Keywords','unknown, wireless, toll free, unlisted')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Superfecta_Cache','Cache_Timeout','120')";
	$db->query($sql);
	
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
	{
		foreach($row as $key=>$val)
		{
			switch($key)
			{
				case 'prefix_url':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base','Prefix_URL','$val')";
					break;
				case 'curl_timeout':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base','Curl_Timeout','$val')";
					break;
				case 'whocalledusername':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Who_Called','Username','$val')";
					break;
				case 'whocalledpassword':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Who_Called','Password','$val')";
					break;
				case 'whocalledthreshold':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Who_Called','SPAM_Threshold','$val')";
					break;
				case 'sugarcrmdbhost':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('SugarCRM','DB_Host','$val')";
					break;
				case 'sugarcrmdbname':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('SugarCRM','DB_Name','$val')";
					break;
				case 'sugarcrmdbuser':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('SugarCRM','DB_User','$val')";
					break;
				case 'sugarcrmdbpassword':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('SugarCRM','DB_Password','$val')";
					break;
				case 'sugarcrmsearchtype':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('SugarCRM','Search_Type','$val')";
					break;
				case 'sources':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base','sources','$val')";
					break;
				case 'spamthreshold':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('PhoneSpamFilter','SPAM_Threshold','$val')";
					break;
				case 'spamtext':
					$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base','SPAM_Text','$val')";
					break;
			}
			$check = $db->query($sql);
			if (DB::IsError($check))
			{
				die_freepbx( "Can not copy value into superfectaconfig table: " . $check->getMessage() .  "\n");
			}
		}
	}
	print 'Copying Values from existing table<br>';
	
	$sql = "DROP TABLE IF EXISTS superfectaoptions";
	$check = $db->query($sql);
	if (DB::IsError($check))
	{
		die_freepbx( "Can not delete superfectaoptions table: " . $check->getMessage() .  "\n");
	}
}

//if the superfectaconfig table is empty, fill in some default values.
$sql = "SELECT * FROM superfectaconfig LIMIT 1;";
$res = $db->query($sql);
if($res->numRows() != 1)
{
	print 'Installing Default Values.<br>';
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_Default','order','0')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_Default','Curl_Timeout','3')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_Default','SPAM_Text','SPAM')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_Default','sources','Asterisk_Phonebook,Superfecta_Cache,Trunk_Provided,Telco_Data')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Trunk_Provided','Ignore_Keywords','unknown, wireless, toll free, unlisted')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('Superfecta_Cache','Cache_Timeout','120')";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('PhoneSpamFilter','SPAM_Threshold','5')";
	$db->query($sql);
}

//determine if this is a pre-scheme database and upgrade if necessary
$sql = "SELECT * FROM superfectaconfig WHERE source = 'base' LIMIT 1";
$res = $db->query($sql);
if($res->numRows() > 0)
{
	//delete supported source files not being used.
	$sql = "SELECT value FROM superfectaconfig WHERE source = 'base' AND field = 'sources' LIMIT 1";
	$res = $db->query($sql);
	if(!DB::IsError($res))
	{	
		while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if($row['value'] != '')
			{
				$sources = explode(',',$row['value']);
				foreach($sources as $key=>$val)
				{
					$sources[$key] = $val;
				}
			}
			
			$supported_sources = array('Addresses','Any_Who','AsteriDex','Asterisk_Phonebook','CanPagesCa','Google','Infobel_Belgium','MySQL_DB','Open79XX','PhoneSpamFilter','SugarCRM','Superfecta_Cache','Telco_Data','Trunk_Provided','VoIPCNAM','White_Pages','Who_Called','Yellow_Pages');
			foreach($supported_sources as $val)
			{
				if(!in_array($val,$sources) && file_exists('modules/superfecta/bin/source-'.$val.'.php'))
				{
					unlink('modules/superfecta/bin/source-'.$val.'.php');
				}
			}
		}
	}
	
	//convert values from the pre-scheme era to a Default Scheme
	$sql = "UPDATE superfectaconfig SET source = 'base_Default' WHERE source = 'base'";
	$db->query($sql);
	$sql = "REPLACE INTO superfectaconfig (source,field,value) VALUES('base_Default','order','0')";
	$db->query($sql);
	$sql = "UPDATE superfectaconfig SET source = CONCAT('Default_',source) WHERE source NOT LIKE 'base_%'";
	$db->query($sql);
}

//adjust from a zero based to a one based order for schemes if neccessary
$sql = "SELECT * FROM superfectaconfig WHERE field = 'order' AND value = 0";
$res = $db->query($sql);
if($res->numRows() > 0)
{
	//order value of zero found
	$sql = "UPDATE superfectaconfig SET value = (value + 1) WHERE field = 'order'";
	$res = $db->query($sql);
}

print 'Verifying Superfecta As Caller ID Source.<br>';

$sql = "SELECT * FROM `cidlookup` WHERE `description` = 'Caller ID Superfecta' LIMIT 1;";
$res = $db->query($sql);

if($res->numRows() != 1)
{
	$cids_lookup_array = array(
		'sourcetype' => 'http',
		'cache' => 0,
		'http_host' => 'localhost',
		'http_path' => '/admin/modules/superfecta/bin/callerid.php',
		'http_query' => 'thenumber=[NUMBER]',
		'description' => 'Caller ID Superfecta',
		'http_username' => $_SERVER["PHP_AUTH_USER"],
		'http_password' => $_SERVER["PHP_AUTH_PW"]
	);
	cidlookup_add($cids_lookup_array);
	print 'Adding Caller ID Superfecta Lookup Source.<br>
	<strong>You may need to add your "maint" user\'s<br>
	ID and Password in the General Options<br>
	section of the Caller ID Superfecta Module\'s<br>
	gui.</strong><br>
	Note: If your password contains any non<br>
	alphanumeric characters, you may need to<br>
	<a href="http://meyerweb.com/eric/tools/dencoder/" target="_blank">URL Encode</a> it first.<br>
	Hopfully this will be fixed in future<br> 
	versions of FreePBX Caller ID Lookup.<br>';

}
else
{
	if($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row['http_path'] = '/admin/modules/superfecta/bin/callerid.php';
		$row['sourcetype'] = 'http';
		unset ($row['cache']);
		$row['http_query'] = 'thenumber=[NUMBER]';
		$row['description'] = 'Caller ID Superfecta';
		$row['http_host'] = 'localhost';
		cidlookup_edit($row['cidlookup_id'],$row);
	}
}
?>
