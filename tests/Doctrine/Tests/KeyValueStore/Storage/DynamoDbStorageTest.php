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

use Doctrine\KeyValueStore\Storage\DynamoDbStorage;
use Aws\DynamoDb\DynamoDbClient;
use Doctrine\KeyValueStore\NotFoundException;

/**
 * @covers \Doctrine\KeyValueStore\Storage\DynamoDbStorage
 */
class DynamoDbStorageTest extends \PHPUnit_Framework_TestCase
{
    const DATA = [
        'author' => 'John Doe',
        'title' => 'example book',
    ];

    /**
     * @var DynamoDbClient|null
     */
    private static $client;

    /**
     * @var DynamoDbStorage
     */
    private $storage;

    public static function setUpBeforeClass()
    {
        $dns = getenv('DYNAMODB_DNS');

        if (empty($dns)) {
            return;
        }

        static::$client = DynamoDbClient::factory(array(
            'credentials' => [
                'key' => 'YOUR_KEY',
                'secret' => 'YOUR_SECRET',
            ],
            'region' => 'us-west-2',
            'endpoint' => $dns,
            'version' => 'latest',
            'retries' => 1,
        ));

        try {
            static::$client->deleteTable([
                'TableName' => 'dynamodb',
            ]);
        } catch (\Exception $exception) {
            // table does not exist
        }

        try {
            static::$client->createTable(array(
                'TableName' => 'dynamodb',
                'AttributeDefinitions' => array(
                    array(
                        'AttributeName' => 'id',
                        'AttributeType' => 'S',
                    ),
                ),
                'KeySchema' => array(
                    array(
                        'AttributeName' => 'id',
                        'KeyType' => 'HASH',
                    ),
                ),
                'ProvisionedThroughput' => array(
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 20,
                ),
            ));
        } catch (\Exception $exception) {
            static::$client = null;
        }
    }

    protected function setUp()
    {
        if (! static::$client) {
            $this->markTestSkipped('DynamoDB is required.');
        }

        $this->storage = new DynamoDbStorage(static::$client);
    }

    public function testInsertAndFind()
    {
        $this->storage->insert('dynamodb', 'testInsertAndFind', self::DATA);

        $data = $this->storage->find('dynamodb', 'testInsertAndFind');

        $this->assertEquals(self::DATA, $data);
    }

    public function testUpdate()
    {
        $this->storage->insert('dynamodb', 'testUpdate', self::DATA);

        $newData = [
            'foo' => 'bar',
        ];

        $this->storage->update('dynamodb', 'testUpdate', $newData);

        $data = $this->storage->find('dynamodb', 'testUpdate');
        $this->assertEquals($newData, $data);
    }

    /**
     * @depends testInsertAndFind
     */
    public function testFindWithNotExistKey()
    {
        $this->setExpectedException(NotFoundException::class);
        $this->storage->find('dynamodb', 'not-existing-key');
    }

    /**
     * @depends testInsertAndFind
     * @depends testFindWithNotExistKey
     */
    public function testDelete()
    {
        $this->storage->insert('dynamodb', 'testDelete', self::DATA);
        $this->storage->delete('dynamodb', 'testDelete');

        $this->setExpectedException(NotFoundException::class);
        $this->storage->find('dynamodb', 'testDelete');
    }

    public function testGetName()
    {
        $this->assertEquals('dynamodb', $this->storage->getName());
    }
}
