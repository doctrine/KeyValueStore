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
use Doctrine\KeyValueStore\InvalidArgumentException;
use Doctrine\KeyValueStore\NotFoundException;

/**
 * DyanmoDb storage
 *
 * @author Stan Lemon <stosh1985@gmail.com>
 */
class DynamoDbStorage implements Storage
{
    /**
     * The key that DynamoDb uses to indicate the name of the table.
     */
    const TABLE_NAME_KEY = 'TableName';

    /**
     * The key that DynamoDb uses to indicate whether or not to do a consistent read.
     */
    const CONSISTENT_READ_KEY = 'ConsistentRead';

    /**
     * The key that is used to refer to the DynamoDb table key.
     */
    const TABLE_KEY = 'Key';

    /**
     * The key that is used to refer to the marshaled item for DynamoDb table.
     */
    const TABLE_ITEM_KEY = 'Item';

    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $client;

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
     * @param DynamoDbClient $client         The client for connecting to AWS DynamoDB.
     * @param Marshaler|null $marshaler      (optional) Marshaller for converting data to/from DynamoDB format.
     * @param string         $defaultKeyName (optional) Default name to use for keys.
     * @param array          $tableKeys      $tableKeys (optional) An associative array for keys representing table names and values
     *                                       representing key names for those tables.
     */
    public function __construct(
      DynamoDbClient $client,
      Marshaler $marshaler = null,
      $defaultKeyName = null,
      array $tableKeys = []
    ) {
        $this->client    = $client;
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
     * @throws InvalidArgumentException When the key name is invalid.
     */
    private function validateKeyName($name)
    {
        if (! is_string($name)) {
            throw InvalidArgumentException::invalidType('key', 'string', $name);
        }

        $len = strlen($name);
        if ($len > 255 || $len < 1) {
            throw InvalidArgumentException::invalidLength('name', 1, 255);
        }
    }

    /**
     * Validates a DynamoDB table name.
     *
     * @see http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/Limits.html
     *
     * @param $name string The table name to validate.
     *
     * @throws InvalidArgumentException When the name is invalid.
     */
    private function validateTableName($name)
    {
        if (! is_string($name)) {
            throw InvalidArgumentException::invalidType('key', 'string', $name);
        }

        if (! preg_match('/^[a-z0-9_.-]{3,255}$/i', $name)) {
            throw InvalidArgumentException::invalidTableName($name);
        }
    }

    /**
     * Sets the default key name for storage tables.
     *
     * @param $name string The default name to use for the key.
     *
     * @throws InvalidArgumentException When the key name is invalid.
     */
    private function setDefaultKeyName($name)
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
     * @throws InvalidArgumentException When the key or table name is invalid.
     */
    private function setKeyForTable($table, $key)
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
    private function getKeyNameForTable($tableName)
    {
        return isset($this->tableKeys[$tableName]) ?
          $this->tableKeys[$tableName] :
          $this->defaultKeyName;
    }

    /**
     * Prepares a key to be in a valid format for lookups for DynamoDB. If passing an array, that means that the key
     * is the name of the key and the value is the actual value for the lookup.
     *
     * @param string $storageName Table name.
     * @param string $key         Key name.
     *
     * @return array The key in DynamoDB format.
     */
    private function prepareKey($storageName, $key)
    {
        if (is_array($key)) {
            $keyValue = reset($key);
            $keyName  = key($key);
        } else {
            $keyValue = $key;
            $keyName  = $this->getKeyNameForTable($storageName);
        }

        return $this->marshaler->marshalItem([$keyName => $keyValue]);
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
        $this->client->putItem([
          self::TABLE_NAME_KEY => $storageName,
          self::TABLE_ITEM_KEY => $this->prepareKey($storageName, $key) + $this->marshaler->marshalItem($this->prepareData($data)),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        //We are using PUT so we just replace the original item, if the key
        //does not exist, it will be created.
        $this->insert($storageName, $key, $this->prepareData($data));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $this->client->deleteItem([
          self::TABLE_NAME_KEY => $storageName,
          self::TABLE_KEY      => $this->prepareKey($storageName, $key),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $item = $this->client->getItem([
          self::TABLE_NAME_KEY      => $storageName,
          self::CONSISTENT_READ_KEY => true,
          self::TABLE_KEY           => $this->prepareKey($storageName, $key),
        ]);

        if (! $item) {
            throw NotFoundException::notFoundByKey($key);
        }

        $item = $item->get(self::TABLE_ITEM_KEY);

        return $this->marshaler->unmarshalItem($item);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'dynamodb';
    }

    /**
     * Prepare data by removing empty item attributes.
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareData($data)
    {
        $callback = function ($value) {
            return $value !== null && $value !== [] && $value !== '';
        };

        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->prepareData($value);
            }
        }
        return array_filter($data, $callback);
    }
}
