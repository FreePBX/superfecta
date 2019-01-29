<?php

/**
 * https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
 * @backupGlobals disabled
 */
class CapsuleCRMTest extends PHPUnit_Framework_TestCase
{
    /** @var CapsuleCRM */
    protected static $o;

    public static function setUpBeforeClass()
    {
        include_once dirname(__DIR__) . '/includes/superfecta_base.php';
        include dirname(__DIR__) . '/sources/source-CapsuleCRM.module';
        self::$o = new CapsuleCRM();
    }

    public function testCname()
    {
        $mock = $this->getMockBuilder('CapsuleCRM')
            ->setMethods(['queryCapsuleCrm'])
            ->getMock();

        // Data CapsuleCRM docs
        $mock->expects($this->any())
            ->method('queryCapsuleCrm')
            ->willReturn('{"parties":[{"id":11587,"type":"person","about":null,"title":null,"firstName":"Scott","lastName":"Spacey","jobTitle":"Creative Director","createdAt":"2015-09-15T10:43:23Z","updatedAt":"2015-09-15T10:43:23Z","organisation":null,"lastContactedAt":null,"owner":null,"team":null,"addresses":[{"id":12135,"type":null,"city":"Chicago","country":"United States","street":"847 North Rush Street","state":"IL","zip":"65629"}],"phoneNumbers":[{"id":12133,"type":null,"number":"773-338-7786"}],"websites":[],"emailAddresses":[{"id":12134,"type":"Work","address":"scott@homestyleshop.co"}],"pictureURL":"https://capsulecrm.com/theme/default/images/person_avatar_70.png"}]}');

        $this->assertEquals('Scott Spacey', $mock->get_caller_id('+447000000000', []));
    }

}
