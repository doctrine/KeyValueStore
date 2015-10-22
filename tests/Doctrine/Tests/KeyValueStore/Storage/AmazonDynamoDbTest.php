<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use Doctrine\KeyValueStore\Storage\AmazonDynamoDbStorage;

class AmazonDynamoDbTest extends \PHPUnit_Framework_TestCase
{
    private function getDynamoDbMock($methods = [])
    {
        $client = $this->getMockBuilder(DynamoDbClient::class)->disableOriginalConstructor();

        if (count($methods)) {
            $client->setMethods($methods);
        }

        return $client->getMock();
    }

    private function getDynamoDbResultMock($methods = [])
    {
        $result = $this->getMockBuilder(Result::class)->disableOriginalConstructor();

        if (count($methods)) {
            $result->setMethods($methods);
        }

        return $result->getMock();
    }

    public function testTheStorageName()
    {
        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client);
        $this->assertSame('amazondynamodb', $storage->getName());
    }

    public function testOptionsMergedCorrectly()
    {
        $options = [
            'storage_keys' => ['this' => 'that', 'yolo' => 'now']
        ];

        $shouldBe = [
            'default_key_name' => 'Id',
            'storage_keys' => ['this' => 'that', 'yolo' => 'now']
        ];

        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, $options);

        $this->assertAttributeSame($shouldBe, 'options', $storage);
    }

    public function testThatSomeStorageHasDifferentKey()
    {
        $options = [
            'default_key_name' => 'sauce',
            'storage_keys' => ['this' => 'that', 'yolo' => 'now']
        ];

        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, $options);

        $r = new \ReflectionObject($storage);
        $method = $r->getMethod('prepareKey');
        $method->setAccessible(true);
        $this->assertSame(['that' => ['N' => '111']], $method->invoke($storage, 'this', 111));
    }

    public function testThatSomeStorageUsesDefaultKey()
    {
        $options = [
            'default_key_name' => 'sauce',
            'storage_keys' => ['this' => 'that', 'yolo' => 'now']
        ];

        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, $options);

        $r = new \ReflectionObject($storage);
        $method = $r->getMethod('prepareKey');
        $method->setAccessible(true);
        $this->assertSame(['sauce' => ['S' => 'hello']], $method->invoke($storage, 'MyTable', "hello"));
    }

    public function testInsertingCallsAPutItem()
    {
        $client = $this->getDynamoDbMock(['putItem']);

        $client->expects($this->once())->method('putItem')->with($this->equalTo([
            'TableName' => 'MyTable',
            'Item' => [
                'Id' => ['S' => 'stuff'],
                'hi' => ['S' => 'there'],
                'yo' => ['BOOL' => false],
            ]
        ]));

        $storage = new AmazonDynamoDbStorage($client);
        $storage->insert('MyTable', 'stuff', ['hi' => 'there', 'yo' => false]);
    }

    public function testUpdateActuallyAlsoCallsInsert()
    {
        $client = $this->getDynamoDbMock(['putItem']);

        $client->expects($this->once())->method('putItem')->with($this->equalTo([
            'TableName' => 'MyTable',
            'Item' => [
                'Id' => ['S' => 'stuff'],
                'hi' => ['S' => 'there'],
                'yo' => ['BOOL' => false],
            ]
        ]));

        $storage = new AmazonDynamoDbStorage($client);
        $storage->update('MyTable', 'stuff', ['hi' => 'there', 'yo' => false]);
    }

    public function testDeleteItem()
    {
        $client = $this->getDynamoDbMock(['deleteItem']);

        $client->expects($this->once())->method('deleteItem')->with($this->equalTo([
            'TableName' => 'MyTable',
            'Key' => ['Id' => ['S' => 'abc123']]
        ]));

        $storage = new AmazonDynamoDbStorage($client);
        $storage->delete('MyTable', 'abc123');
    }

    public function testPassingArrayAsKeyIsAPassthruToInsert()
    {
        $client = $this->getDynamoDbMock(['deleteItem']);

        $client->expects($this->once())->method('deleteItem')->with($this->equalTo([
            'TableName' => 'MyTable',
            'Key' => ['Id' => ['S' => 'abc123']]
        ]));

        $storage = new AmazonDynamoDbStorage($client);
        $storage->delete('MyTable', 'abc123');
    }

    /**
     * @expectedException \Doctrine\KeyValueStore\NotFoundException
     */
    public function testTryingToFindAnItemThatDoesNotExist()
    {
        $client = $this->getDynamoDbMock(['getItem']);
        $client->expects($this->once())->method('getItem')->with($this->equalTo([
            'TableName' => 'MyTable',
            'ConsistentRead' => true,
            'Key' => ['Id' => ['N' => '1000']]
        ]))->willReturn(null);

        $storage = new AmazonDynamoDbStorage($client);
        $storage->find('MyTable', 1000);
    }

    public function testFindAnItemThatExists()
    {
        $result = $this->getDynamoDbResultMock(['get']);
        $result->expects($this->once())->method('get')->with('Item')->willReturn([
            'hello' => ['S' => 'world']
        ]);

        $client = $this->getDynamoDbMock(['getItem']);
        $client->expects($this->once())->method('getItem')->with($this->equalTo([
            'TableName' => 'MyTable',
            'ConsistentRead' => true,
            'Key' => ['Id' => ['N' => '1000']]
        ]))->willReturn($result);

        $storage = new AmazonDynamoDbStorage($client);
        $actualResult = $storage->find('MyTable', 1000);

        $this->assertSame(['hello' => 'world'], $actualResult);
    }
}
