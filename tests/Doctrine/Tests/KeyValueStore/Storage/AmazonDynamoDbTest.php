<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\AmazonDynamoDbStorage;

class AmazonDynamoDbTest extends \PHPUnit_Framework_TestCase
{
    private function getDynamoDbMock($methods = [])
    {
        $client = $this->getMockBuilder('Aws\DynamoDb\DynamoDbClient')->disableOriginalConstructor();

        if (count($methods)) {
            $client->setMethods($methods);
        }

        return $client->getMock();
    }

    private function getDynamoDbResultMock($methods = [])
    {
        $result = $this->getMockBuilder('Aws\Result')->disableOriginalConstructor();

        if (count($methods)) {
            $result->setMethods($methods);
        }

        return $result->getMock();
    }

    public function testTheStorageName()
    {
        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client);
        $this->assertSame('amazon_dynamodb', $storage->getName());
    }

    public function testDefaultKeyName()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $this->assertAttributeSame('Id', 'defaultKeyName', $storage);
    }

    public function testThatTableKeysInitiallyEmpty()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $this->assertAttributeSame([], 'tableKeys', $storage);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The key must be a string, got "array" instead.
     */
    public function testDefaultKeyCannotBeSomethingOtherThanString()
    {
        $client = $this->getDynamoDbMock();
        new AmazonDynamoDbStorage($client, null, []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The key must be a string, got "object" instead.
     */
    public function testTableKeysMustAllBeStringsOrElse()
    {
        $client = $this->getDynamoDbMock();
        new AmazonDynamoDbStorage($client, null, null, ['mytable' => 'hello', 'yourtable' => new \stdClass()]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The name must not exceed 255 bytes.
     */
    public function testKeyNameMustBeUnder255Bytes()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $storage->setDefaultKeyName(str_repeat('a', 256));
    }

    public function invalidTableNames()
    {
        return [
            ['a2'],
            ['yo%'],
            ['что'],
            ['h@llo']
        ];
    }

    public function validTableNames()
    {
        return [
            ['MyTable'],
            ['This_is0k-...'],
            ['hello_world'],
            ['...........00....']
        ];
    }

    /**
     * @dataProvider invalidTableNames
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid DynamoDB table name.
     */
    public function testTableNameValidatesAgainstInvalidTableNames($tableName)
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $storage->setKeyForTable($tableName, 'Id');
    }

    /**
     * @dataProvider validTableNames
     */
    public function testTableNameValidatesAgainstValidTableNames($tableName)
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $storage->setKeyForTable($tableName, 'Id');

        $this->assertAttributeSame([$tableName => 'Id'], 'tableKeys', $storage);
    }

    public function testThatYouCanHaveMultipleTablesWithOverrides()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $storage->setKeyForTable('Aaa', '2');
        $storage->setKeyForTable('Bbb', '1');

        $this->assertAttributeSame(['Aaa' => '2', 'Bbb' => '1'], 'tableKeys', $storage);
    }

    public function testGetterForDefaultKeyName()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client, null, 'CustomKey');
        $this->assertSame('CustomKey', $storage->getDefaultKeyName());
    }

    public function testGetWillReturnDefaultKeyForUnrecognizedTableName()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client, null, 'CustomKey');
        $this->assertSame('CustomKey', $storage->getKeyNameForTable('whatever_this_is'));
    }

    public function testGetWillReturnCorrectKeyForRecognizedTableName()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client, null, 'CustomKey', ['MyTable' => 'Yesss']);
        $this->assertSame('Yesss', $storage->getKeyNameForTable('MyTable'));
    }

    public function testThatSomeStorageHasDifferentKey()
    {
        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, 'sauce', ['this' => 'that', 'yolo' => 'now']);

        $r = new \ReflectionObject($storage);
        $method = $r->getMethod('prepareKey');
        $method->setAccessible(true);
        $this->assertSame(['that' => ['N' => '111']], $method->invoke($storage, 'this', 111));
    }

    public function testThatSomeStorageUsesDefaultKey()
    {
        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, 'sauce', ['this' => 'that', 'yolo' => 'now']);

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
