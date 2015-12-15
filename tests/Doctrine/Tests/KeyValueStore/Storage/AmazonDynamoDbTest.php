<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\AmazonDynamoDbStorage;

/**
 * @covers \Doctrine\KeyValueStory\Storage\AmazonDynamoDbStorage
 */
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
     * @expectedException \Doctrine\KeyValueStore\KeyValueStoreException
     * @expectedExceptionMessage The key must be a string, got "array" instead.
     */
    public function testDefaultKeyCannotBeSomethingOtherThanString()
    {
        $client = $this->getDynamoDbMock();
        new AmazonDynamoDbStorage($client, null, []);
    }

    /**
     * @expectedException \Doctrine\KeyValueStore\KeyValueStoreException
     * @expectedExceptionMessage The key must be a string, got "object" instead.
     */
    public function testTableKeysMustAllBeStringsOrElse()
    {
        $client = $this->getDynamoDbMock();
        new AmazonDynamoDbStorage($client, null, null, ['mytable' => 'hello', 'yourtable' => new \stdClass()]);
    }

    /**
     * @expectedException \Doctrine\KeyValueStore\KeyValueStoreException
     * @expectedExceptionMessage The name must be at least 1 but no more than 255 chars.
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

    private function invokeMethod($methodName, $obj, array $args = null)
    {
        $relf = new \ReflectionObject($obj);
        $method = $relf->getMethod($methodName);
        $method->setAccessible(true);

        if ($args) {
            return $method->invokeArgs($obj, $args);
        }

        return $method->invoke($obj);
    }

    /**
     * @dataProvider invalidTableNames
     * @expectedException \Doctrine\KeyValueStore\KeyValueStoreException
     */
    public function testTableNameValidatesAgainstInvalidTableNames($tableName)
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $this->invokeMethod('setKeyForTable', $storage, [$tableName, 'Id']);
    }

    /**
     * @dataProvider validTableNames
     */
    public function testTableNameValidatesAgainstValidTableNames($tableName)
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $this->invokeMethod('setKeyForTable', $storage, [$tableName, 'Id']);

        $this->assertAttributeSame([$tableName => 'Id'], 'tableKeys', $storage);
    }

    public function testThatYouCanHaveMultipleTablesWithOverrides()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client);
        $this->invokeMethod('setKeyForTable', $storage, ['Aaa', '2']);
        $this->invokeMethod('setKeyForTable', $storage, ['Bbb', '1']);

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
        $this->assertSame('CustomKey', $this->invokeMethod('getKeyNameForTable', $storage, ['whatever_this_is']));
    }

    public function testGetWillReturnCorrectKeyForRecognizedTableName()
    {
        $client = $this->getDynamoDbMock();
        $storage = new AmazonDynamoDbStorage($client, null, 'CustomKey', ['MyTable' => 'Yesss']);
        $this->assertSame('Yesss', $this->invokeMethod('getKeyNameForTable', $storage, ['MyTable']));
    }

    public function testThatSomeStorageHasDifferentKey()
    {
        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, 'sauce', ['this' => 'that', 'yolo' => 'now']);

        $this->assertSame(['that' => ['N' => '111']], $this->invokeMethod('prepareKey', $storage, ['this', 111]));
    }

    public function testThatSomeStorageUsesDefaultKey()
    {
        $client = $this->getDynamoDbMock();

        $storage = new AmazonDynamoDbStorage($client, null, 'sauce', ['this' => 'that', 'yolo' => 'now']);

        $this->assertSame(['sauce' => ['S' => 'hello']], $this->invokeMethod('prepareKey', $storage, ['MyTable', "hello"]));
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
