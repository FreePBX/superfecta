Caller ID Superfecta is being uninstalled.<br>
<?php
global $db;
global $amp_conf;
global $astman;

if(!function_exists("out"))
{
	function out($text)
	{
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

// drop the tables
$sql = "DROP TABLE IF EXISTS superfectaconfig";
$check = $db->query($sql);
if (DB::IsError($check))
{
	die_freepbx( "Can not delete superfectaconfig table: " . $check->getMessage() .  "\n");
}

$sql = "DROP TABLE IF EXISTS superfectacache";
$check = $db->query($sql);
if (DB::IsError($check))
{
	die_freepbx( "Can not delete superfectacache table: " . $check->getMessage() .  "\n");
}
	print 'Deleting Caller ID Superfecta Inbound Route Assignments, and performing general cleanup.<br>';
	//delete incoming lookups
$sql = "delete c1 from cidlookup_incoming c1, cidlookup c2 where c1.cidlookup_id = c2.cidlookup_id and  c2.description = 
'Caller ID Superfecta'";
$res = $db->query($sql);
//delete the line from the cidlookup table
$sql = "DELETE FROM cidlookup WHERE description = 'Caller ID Superfecta'";
$res = $db->query($sql);
//cleanup stray cidlookup_incoming records left by bad uninstalls
$sql = "delete c1 from cidlookup_incoming c1 left outer join cidlookup c2 on c1.cidlookup_id = c2.cidlookup_id where 
c2.cidlookup_id is null";
$res = $db->query($sql);
?>
