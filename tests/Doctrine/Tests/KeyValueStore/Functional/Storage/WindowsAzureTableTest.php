<?php
namespace Doctrine\Tests\KeyValueStore\Functional\Storage;

use Doctrine\Tests\KeyValueStoreTestCase;
use Doctrine\KeyValueStore\Storage\WindowsAzureTableStorage;
use Doctrine\KeyValueStore\Storage\WindowsAzureTable\SharedKeyLiteAuthorization;
use Doctrine\KeyValueStore\Http\StreamClient;

class WindowsAzureTableTest extends KeyValueStoreTestCase
{
    public function testCrud()
    {
        if (empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME']) || empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY'])) {
            $this->markTestSkipped("Missing Azure credentials.");
        }

        switch ($GLOBALS['DOCTRINE_KEYVALUE_AZURE_AUTHSCHEMA']) {
            case 'shared':
                $auth = new SharedKeyLiteAuthorization(
                    $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
                    $GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY']
                );
                break;
        }

        $storage = new WindowsAzureTableStorage(
            new StreamClient(),
            $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
            $auth
        );

        $response = $storage->createTable("test");
        var_dump($reponse);
        #$storage->insert("test", array("dist" => "foo", "range" => 100), array("foo" => "bar"));
    }
}

