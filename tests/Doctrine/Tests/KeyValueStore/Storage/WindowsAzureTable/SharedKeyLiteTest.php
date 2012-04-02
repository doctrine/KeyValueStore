<?php

namespace Doctrine\Tests\KeyValueStore\Storage\WindowsAzureTable;

use Doctrine\KeyValueStore\Storage\WindowsAzureTable\SharedKeyLiteAuthorization;

class SharedKeyLiteTest extends \PHPUnit_Framework_TestCase
{
    private $auth;

    public function setUp()
    {
        $this->auth = new SharedKeyLiteAuthorization(
            "testing",
            "abcdefg"
        );
    }

    public function testKeyGeneration1()
    {
        $authorization = $this->auth->signRequest('GET', '/', '', '', array(
            "x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"
        ));
        $this->assertEquals(
            "Authorization: SharedKeyLite testing:vZdOn/j0gW5FG0kAUG9NhSBO9eBjZqfe6RwALPYUtqU=",
            $authorization
        );
    }

    public function testKeyGeneration2()
    {
        $authorization = $this->auth->signRequest('GET', '/test', '', '', array(
            "x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"
        ));
        $this->assertEquals(
            "Authorization: SharedKeyLite testing:HJTSiRDtMsQVsFVispSHkcODeFykLO+WEuOepwmh51o=",
            $authorization
        );
    }
}

