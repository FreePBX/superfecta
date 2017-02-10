
<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
global $amp_conf, $db;
include '/etc/freepbx.conf';
restore_error_handler();
error_reporting(-1);
error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
