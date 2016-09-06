<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class Who_CalledTest extends PHPUnit_Framework_TestCase{
	protected static $o;
	public static function setUpBeforeClass() {
			include 'setuptests.php';
			$webroot = FreePBX::Config()->get('AMPWEBROOT');
			include $webroot.'/admin/modules/superfecta/includes/superfecta_base.php';
			include $webroot.'/admin/modules/superfecta/sources/source-Who_Called.module';
			self::$o = new Who_Called();
	}
	//Stuff before the test
	public function setup() {}
	//Leave this alone, it test that PHPUnit is working
	public function testPHPUnit() {
			$this->assertEquals("test", "test", "PHPUnit is broken.");
			$this->assertNotEquals("test", "nottest", "PHPUnit is broken.");
	}

	//This tests that the the object for your class is an object
	public function testCreate() {;
			$this->assertTrue(is_object(self::$o), "Did not get an object");
	}

	public function testCnam(){
		$cnam = self::$o->get_caller_id('6305424316',array('CNAM_Lookup' => true));
		$this->assertEquals("Schaumburg%2C+IL", $cnam, "The lookup returned an unexpected result for 6305434316");
	}
}
