<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class TelefonABC_AustriaTest extends PHPUnit_Framework_TestCase{
	protected static $o;
	public static function setUpBeforeClass() {
			include_once dirname(__DIR__).'/includes/superfecta_base.php';
			include dirname(__DIR__).'/sources/source-TelefonABC_Austria.module';
			self::$o = new TelefonABC_Austria();
	}

	public function testCnam(){
		$cnam = self::$o->get_caller_id('+43727727729');
		$this->assertEquals("Mayr                Barbara", $cnam, "The lookup returned an unexpected result for +43727727729");
	}
}
