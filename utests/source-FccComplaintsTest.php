<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class FccComplaintsTest extends PHPUnit_Framework_TestCase{
	protected static $o;
	public static function setUpBeforeClass() {
			include_once dirname(__DIR__).'/includes/superfecta_base.php';
			include dirname(__DIR__).'/sources/source-FccComplaints.module';
			self::$o = new FccComplaints();
	}

	public function testCnam(){
		/*
		$cnam = self::$o->get_caller_id('6305424316');
		$this->assertEquals("Schaumburg%2C+IL", $cnam, "The lookup returned an unexpected result for 6305434316");
		*/
	}
}
