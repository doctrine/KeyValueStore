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
use Aws\DynamoDb\Marshaler;
use Doctrine\KeyValueStore\NotFoundException;

class AmazonDynamoDbStorage implements Storage
{
    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var Marshaler
     */
    private $marshaler;

    /**
     * @var string
     */
    private $defaultKeyName = 'Id';

    /**
     * A associative array where the key is the table name and the value is the name of the key.
     *
     * @var array
     */
    private $tableKeys = [];

    /**
     * @param DynamoDbClient $client    The client for connecting to AWS DynamoDB.
     * @param Marshaler|null $marshaler (optional) Marshaller for converting data to/from DynamoDB format.
     * @param string         $defaultKeyName   (optional) Default name to use for keys.
     * @param array $tableKeys $tableKeys (optional) An associative array for keys representing table names and values
     * representing key names for those tables.
     */
    public function __construct(
        DynamoDbClient $client,
        Marshaler $marshaler = null,
        $defaultKeyName = null,
        array $tableKeys = []
    ) {
        $this->client = $client;
        $this->marshaler = $marshaler ?: new Marshaler();

        if ($defaultKeyName !== null) {
            $this->setDefaultKeyName($defaultKeyName);
        }

        foreach ($tableKeys as $table => $keyName) {
            $this->setKeyForTable($table, $keyName);
        }
    }

    /**
     * Validates a DynamoDB key name.
     *
     * @param $name mixed The name to validate.
     *
     * @throws \InvalidArgumentException When the key name is invalid.
     */
    private function  validateKeyName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf('The key must be a string, got "%s" instead.', gettype($name))
            );
        }

        $len = strlen($name);
        if ($len > 255 || $len < 1) {
            throw new \InvalidArgumentException('The name must not exceed 255 bytes.');
        }
    }

    /**
     * Validates a DynamoDB table name.
     *
     * @see http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/Limits.html
     *
     * @param $name string The table name to validate.
     *
     * @throws \InvalidArgumentException When the name is invalid.
     */
    private function  validateTableName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf('The key must be a string, got "%s" instead.', gettype($name))
            );
        }

        if (!preg_match('/^[a-z0-9_.-]{3,255}$/i', $name)) {
            throw new \InvalidArgumentException('Invalid DynamoDB table name.');
        }
    }

    /**
     * Sets the default key name for storage tables.
     *
     * @param $name string The default name to use for the key.
     *
     * @throws \InvalidArgumentException When the key name is invalid.
     */
    public function setDefaultKeyName($name)
    {
        $this->validateKeyName($name);
        $this->defaultKeyName = $name;
    }

    /**
     * Retrieves the default key name.
     *
     * @return string The default key name.
     */
    public function getDefaultKeyName()
    {
        return $this->defaultKeyName;
    }

    /**
     * Sets a key name for a specific table.
     *
     * @param $table string The name of the table.
     * @param $key string The name of the string.
     *
     * @throws \InvalidArgumentException When the key or table name is invalid.
     */
    public function setKeyForTable($table, $key)
    {
        $this->validateTableName($table);
        $this->validateKeyName($key);
        $this->tableKeys[$table] = $key;
    }

    /**
     * Retrieves a specific name for a key for a given table. The default is returned if this table does not have
     * an actual override.
     *
     * @param string $tableName The name of the table.
     *
     * @return string
     */
    public function getKeyNameForTable($tableName)
    {
        return isset($this->tableKeys[$tableName]) ?
            $this->tableKeys[$tableName] :
            $this->defaultKeyName;
    }

    /**
     * Prepares a key to be in a valid format for lookups for DynamoDB. If passing an array, that means that the key
     * is the name of the key and the value is the actual value for the lookup.
     *
     * @param string $storageName
     *
     * @return array The key in DynamoDB format.
     */
    private function  prepareKey($storageName, $key)
    {
        if (is_array($key)) {
            $keyValue = reset($key);
            $keyName = key($key);
        } else {
            $keyValue = $key;
            $keyName = $this->getKeyNameForTable($storageName);
        }

        return $this->marshaler->marshalItem([$keyName => $keyValue]);
    }

    /**
     * Determine if the storage supports updating only a subset of properties,
     * or if all properties have to be set, even if only a subset of properties
     * changed.
     *
     * @return bool
     */
    public function supportsPartialUpdates()
    {
        //This is not true, but partial updates are too complicated given the available interface,
        //meaning, the abstraction is insufficiently flexible enough to support this type of action.
        return false;
    }

    /**
     * Does this storage support composite primary keys?
     *
     * @return bool
     */
    public function supportsCompositePrimaryKeys()
    {
        return true;
    }

    /**
     * Does this storage require composite primary keys?
     *
     * @return bool
     */
    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * Insert data into the storage key specified.
     *
     * @param string       $storageName
     * @param array|string $key
     * @param array        $data
     */
    public function insert($storageName, $key, array $data)
    {
        $this->client->putItem([
            'TableName' => $storageName,
            'Item' => $this->prepareKey($storageName, $key) + $this->marshaler->marshalItem($data),
        ]);
    }

    /**
     * Update data into the given key.
     *
     * @param string       $storageName
     * @param array|string $key
     * @param array        $data
     */
    public function update($storageName, $key, array $data)
    {
        //We are using PUT so we just replace the original item
        $this->insert($storageName, $key, $data);
    }

    /**
     * Delete data at key.
     *
     * @param string       $storageName
     * @param array|string $key
     */
    public function delete($storageName, $key)
    {
        $this->client->deleteItem([
            'TableName' => $storageName,
            'Key' => $this->prepareKey($storageName, $key),
        ]);
    }

    /**
     * Find data at key.
     *
     * Important note: The returned array does contain the identifier (again)!
     *
     * @throws NotFoundException When data with key is not found.
     *
     * @param string       $storageName
     * @param array|string $key
     *
     * @return array
     */
    public function find($storageName, $key)
    {
        $item = $this->client->getItem([
            'TableName' => $storageName,
            'ConsistentRead' => true,
            'Key' => $this->prepareKey($storageName, $key),
        ]);

        if (!$item) {
            throw new NotFoundException();
        }

        $item = $item->get('Item');
        if (!is_array($item)) {
            throw new NotFoundException();
        }

        return $this->marshaler->unmarshalItem($item);
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'amazon_dynamodb';
    }
}
