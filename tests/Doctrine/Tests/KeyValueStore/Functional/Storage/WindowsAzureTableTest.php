<?php
namespace Doctrine\Tests\KeyValueStore\Functional\Storage;

use Doctrine\Tests\KeyValueStoreTestCase;
use Doctrine\KeyValueStore\Storage\WindowsAzureTableStorage;
use Doctrine\KeyValueStore\Storage\WindowsAzureTable\SharedKeyLiteAuthorization;
use Doctrine\KeyValueStore\Http\SocketClient;

class WindowsAzureTableTest extends KeyValueStoreTestCase
{
    public function testCrud()
    {
        if (empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME']) || empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY'])) {
            $this->markTestSkipped("Missing Azure credentials.");
        }

        switch ($GLOBALS['DOCTRINE_KEYVALUE_AZURE_AUTHSCHEMA']) {
            case 'sharedlite':
                $auth = new SharedKeyLiteAuthorization(
                    $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
                    $GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY']
                );
                break;
        }

        $storage = new WindowsAzureTableStorage(
            new SocketClient(),
            $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
            $auth
        );

        $key = array("dist" => "foo", "range" => time());
        $storage->insert("test", $key, array("foo" => "bar"));
        $data = $storage->find("test", $key);

        $this->assertInstanceOf('DateTime', $data['Timestamp']);
        $this->assertEquals('bar', $data['foo']);
        $this->assertEquals('foo', $data['dist']);
        $this->assertEquals($key['range'], $data['range']);

        $storage->update("test", $key, array("foo" => "baz", "bar" => "baz"));
        $data = $storage->find("test", $key);

        $this->assertEquals('baz', $data['foo']);
        $this->assertEquals('baz', $data['bar']);

        $storage->delete("test", $key);
        $data = $storage->find("test", $key);
        $this->assertEquals(array(), $data);
    }
}

