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

namespace Doctrine\KeyValueStore\Storage;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\ResourceNotFoundException;
use Aws\DynamoDb\Iterator\ItemIterator;
use Doctrine\KeyValueStore\Exception\NotFoundException;

/**
 * DyanmoDb storage
 *
 * @author Stan Lemon <stosh1985@gmail.com>
 */
class DynamoDbStorage implements Storage
{
    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $client;

    /**
     * Constructor
     *
     * @param \Aws\DynamoDb\DynamoDbClient $client
     */
    public function __construct(DynamoDbClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function insert($storageName, $key, array $data)
    {
        $this->createTable($storageName);

        $this->prepareData($key, $data);

        $result = $this->client->putItem([
            'TableName'              => $storageName,
            'Item'                   => $this->client->formatAttributes($data),
            'ReturnConsumedCapacity' => 'TOTAL',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $this->prepareData($key, $data);

        unset($data['id']);

        foreach ($data as $k => $v) {
            $data[$k] = [
                'Value' => $this->client->formatValue($v),
            ];
        }

        $result = $this->client->updateItem([
            'TableName' => $storageName,
            'Key'       => [
                'id' => ['S' => $key],
            ],
            'AttributeUpdates' => $data,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $result = $this->client->deleteItem([
            'TableName' => $storageName,
            'Key'       => [
                'id' => ['S' => $key],
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $iterator = new ItemIterator($this->client->getScanIterator([
            'TableName' => $storageName,
            'Key'       => [
                'Id' => ['S' => $key],
            ],
        ]));

        $results = $iterator->toArray();

        if (count($results)) {
            return array_shift($results);
        }

        throw new NotFoundException();
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'dynamodb';
    }

    /**
     * @param string $tableName
     */
    protected function createTable($tableName)
    {
        try {
            $this->client->describeTable([
                'TableName' => $tableName,
            ]);
        } catch (ResourceNotFoundException $e) {
            $this->client->createTable([
                'AttributeDefinitions' => [
                    [
                        'AttributeName' => 'id',
                        'AttributeType' => 'S',
                    ],
                ],
                'TableName' => $tableName,
                'KeySchema' => [
                    [
                        'AttributeName' => 'id',
                        'KeyType'       => 'HASH',
                    ],
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits'  => 1,
                    'WriteCapacityUnits' => 1,
                ],
            ]);
        }
    }

    /**
     * @param string $key
     * @param array  $data
     */
    protected function prepareData($key, &$data)
    {
        $data = array_merge($data, [
            'id' => $key,
        ]);

        foreach ($data as $key => $value) {
            if ($value === null || $value === [] || $value === '') {
                unset($data[$key]);
            }
        }
    }
}
