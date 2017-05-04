<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class Herold_AustriaTest extends PHPUnit_Framework_TestCase{
	protected static $o;
	public static function setUpBeforeClass() {
			include_once dirname(__DIR__).'/includes/superfecta_base.php';
			include dirname(__DIR__).'/sources/source-Herold_Austria.module';
			self::$o = new Herold_Austria();
	}

	public function testCnam(){
		$cnam = self::$o->get_caller_id('+437252799');
		$this->assertEquals("Mitterhuemer MENSCH | ENERGIE | TECHNIK", $cnam, "The lookup returned an unexpected result for +437252799");
	}
}
