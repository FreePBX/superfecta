<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class DasTelefonBuch_GermanyTest extends PHPUnit_Framework_TestCase{
	protected static $o;
	public static function setUpBeforeClass() {
			include_once dirname(__DIR__).'/includes/superfecta_base.php';
			include dirname(__DIR__).'/sources/source-DasTelefonBuch_Germany.module';
			self::$o = new DasTelefonBuch_Germany();
	}

	public function testCnam(){
		$cnam = self::$o->get_caller_id('+4998315050');
		$this->assertEquals("Hetzner Online AG Internetdienstleistungen", $cnam, "The lookup returned an unexpected result for +4998315050");
	}
}
