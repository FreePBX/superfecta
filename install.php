<?php
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
$fs = new Filesystem();

if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

// Set execute permissions for AGI script
#@chmod(dirname(__FILE__) . '/agi/superfecta.agi', 0755);
try {
	$fs->chmod(__DIR__.'/agi/superfecta.agi', 0755);
} catch (IOExceptionInterface $e) {
	out(sprintf("Couldn't set permissions on %s please run fwconsole chown from the command line",__DIR__.'/agi/superfecta.agi'));
}

global $db;
// Remove entries from Caller ID Lookup sources left by legacy Superfecta Installs
$sql = "SELECT * FROM `cidlookup` WHERE `description` = 'Caller ID Superfecta'";
$res = $db->query($sql);
if ( !DB::IsError($res) && $res->numRows() != 0 ) {
	echo "Cleaning up remnants of legacy Superfecta installation.</p>";
	$sql = "DELETE FROM cidlookup WHERE description = 'Caller ID Superfecta'";
	$res = $db->query($sql);
}

// remove spurious superfecta cache entries caused by previous versions of Trunk Provided module:
$sql = "select * from superfectacache where `callerid` like 'CID Superfecta!'";
$res = $db->query($sql);
if ( !DB::IsError($res) && $res->numRows() != 0 ) {
	echo "Cleaning up Superfecta Cache pollution from Trunk Provided module.</p>";
	$sql = "DELETE FROM superfectacache where `callerid` like 'CID Superfecta!'";
	$res = $db->query($sql);
}