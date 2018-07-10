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
