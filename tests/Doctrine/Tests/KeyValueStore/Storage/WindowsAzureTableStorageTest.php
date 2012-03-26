<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

class WindowsAzureTableStorageTest extends AbstractStorageTestCase
{
    protected function createStorage()
    {
        if ( empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME']) || empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY'])) {
            $this->markTestSkipped("No Azure information provided.");
        }

        switch ($GLOBALS['DOCTRINE_KEYVALUE_AZURE_AUTHSCHEMA']) {
            case 'shared':
                $auth = new SharedKeyAuthorization();
                break;
            case 'sharedlite':
                $auth = new SharedKeyLiteAuthorization();
                break;
            default:
                $this->markTestSkipped("Unknown auth schema " . $GLOBALS['DOCTRINE_KEYVALUE_AZURE_AUTHSCHEMA']);
        }

        $storage = new WindowsAzureTableStorage(
            $client, $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'], $auth
        );

        return $storage;
    }
}

