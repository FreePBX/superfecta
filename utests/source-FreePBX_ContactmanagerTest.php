<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class FreePBX_ContactmanagerTest extends PHPUnit_Framework_TestCase{
	protected static $o;
	protected static $gid;
	protected static $randnum;
	protected static $randdisplayname;
	protected static $randfname;
	protected static $randlname;
	protected static $randcompany;

	public static function setUpBeforeClass() {
			include_once dirname(__DIR__).'/includes/superfecta_base.php';
			include dirname(__DIR__).'/sources/source-FreePBX_Contactmanager.module';
			self::$o = new FreePBX_Contactmanager();
			self::$randnum = rand(1111111111,9999999999);
			self::$randdisplayname = 'displayname'.substr( md5(rand()), 0, 7);
			self::$randfname = 'fname'.substr( md5(rand()), 0, 7);
			self::$randlname = 'lname'.substr( md5(rand()), 0, 7);
			self::$randcompany = 'company'.substr( md5(rand()), 0, 7);
	}
	//Stuff before the test
	public function setup() {
		$group = FreePBX::Contactmanager()->addGroup("testgroup","external");
		FreePBX::Contactmanager()->addEntryByGroupID($group['id'], array(
			'user' => -1,
			'displayname' => self::$randdisplayname,
			'fname' => self::$randfname,
			'lname' => self::$randlname,
			'company' => self::$randcompany,
			'numbers' => array(
				array(
					'number' => self::$randnum
				)
			)
		));
		self::$gid = $group['id'];
	}

	public function testFormat1(){
		$cnam = self::$o->get_caller_id(self::$randnum,array('Return_Format' => 1));
		$this->assertEquals(self::$randdisplayname, $cnam, "The lookup returned an unexpected result for ".self::$randnum);
	}

	public function testFormat2(){
		$cnam = self::$o->get_caller_id(self::$randnum,array('Return_Format' => 2));
		$this->assertEquals(self::$randcompany, $cnam, "The lookup returned an unexpected result for ".self::$randnum);
	}

	public function testFormat3(){
		$cnam = self::$o->get_caller_id(self::$randnum,array('Return_Format' => 3));
		$this->assertEquals(self::$randlname." ".self::$randfname, $cnam, "The lookup returned an unexpected result for ".self::$randnum);
	}

	public function testFormat4(){
		$cnam = self::$o->get_caller_id(self::$randnum,array('Return_Format' => 4));
		$this->assertEquals(self::$randfname." ".self::$randlname, $cnam, "The lookup returned an unexpected result for ".self::$randnum);
	}

	public function tearDown() {
		FreePBX::Contactmanager()->deleteGroupByID(self::$gid);
	}
}
