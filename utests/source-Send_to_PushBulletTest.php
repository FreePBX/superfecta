<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/

class Send_to_PushBulletTest extends PHPUnit_Framework_TestCase{
	protected static $o;
  protected static $api_key;
  protected static $device;
  protected static $sync;
	public static function setUpBeforeClass() {
			include 'setuptests.php';
			$webroot = FreePBX::Config()->get('AMPWEBROOT');
			include $webroot.'/admin/modules/superfecta/includes/superfecta_base.php';
			include $webroot.'/admin/modules/superfecta/sources/source-Send_to_PushBullet.module';
      if(file_exists(__DIR__.'/source-Send_to_PushBullet.auth')){
        include __DIR__.'/source-Send_to_PushBullet.auth';
      }else{
        die("You must create a ./source-Send_to_PushBullet.auth with API credentials");
      }
			self::$o = new Send_to_PushBullet();
      self::$api_key = $auth_api_key;
      self::$device = $auth_device;
      self::$sync = $auth_sync;
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

	public function testGetDevices(){
		$ret = self::$o->getDevices(array('API' => self::$api_key));
    $obj = json_decode(unserialize($ret),true);
    $this->assertArrayHasKey('devices',$obj);
	}
  public function testPush(){
    $ret = json_decode(self::$o->post_processing($cache_found, $winning_source, 'UNIT TEST', array('API' => self::$api_key), '5551212'),true);
    $this->assertTrue($ret['active'], "Push response active");
  }
}
