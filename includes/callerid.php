#!/usr/bin/php
<?php
/**
 * Multifecta child runner. Spawned by superfecta_multi so each source
 * can look up CNAM in parallel and write the result back to superfecta_mf_child.
 */
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}

$cliopts = getopt('d:s:m:r:t:');
if (empty($cliopts['m']) || empty($cliopts['s']) || empty($cliopts['r'])) {
	fwrite(STDERR, "Usage: callerid.php -s <scheme> -m <multifecta_child_id> -r <source> [-t <trunk_info>] [-d <debug>]\n");
	exit(1);
}

$debug = isset($cliopts['d']) ? $cliopts['d'] : 0;
$trunk_info = array();
if (!empty($cliopts['t'])) {
	$decoded = json_decode(base64_decode($cliopts['t']), true);
	if (is_array($decoded)) {
		$trunk_info = $decoded;
	}
}

require_once __DIR__ . '/superfecta_base.php';
require_once __DIR__ . '/processors/superfecta_multi.php';

global $db, $amp_conf, $astman;

$scheme = $cliopts['s'];
$scheme_name = (strpos($scheme, 'base_') === 0) ? $scheme : 'base_'.$scheme;
$settings = FreePBX::Superfecta()->getScheme(preg_replace('/^base_/', '', $scheme_name));
if (empty($settings)) {
	fwrite(STDERR, "Unknown scheme\n");
	exit(1);
}
if (isset($settings['sources']) && is_array($settings['sources'])) {
	$settings['sources'] = implode(',', array_filter($settings['sources']));
}

$pearDb = (isset($db) && is_object($db) && method_exists($db, 'quoteSmart')) ? $db : new \DB(FreePBX::Database());

$superfecta = new superfecta_multi(array(
	'db' => $pearDb,
	'amp_conf' => isset($amp_conf) ? $amp_conf : array(),
	'astman' => isset($astman) ? $astman : null,
	'debug' => $debug,
	'scheme_name' => $scheme_name,
	'scheme_parameters' => $settings,
	'path_location' => dirname(__DIR__) . '/sources',
	'trunk_info' => $trunk_info,
	'multifecta_id' => $cliopts['m'],
	'source' => $cliopts['r'],
));
$superfecta->setCLI(true);
$superfecta->setDebug($debug);
$superfecta->get_results();
