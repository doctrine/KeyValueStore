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

class AmazonDynamoDbStorage implements  Storage
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
     * @var array
     */
    private $options = [
        'default_key_name' => 'Id',
        'storage_keys' => [],
    ];

    /**
     * @param DynamoDbClient $client    The client for connecting to AWS DynamoDB.
     * @param Marshaler|null $marshaler (optional) Marshaller for converting data to/from DynamoDB format.
     * @param array          $options   (optional) Options to set which names to use for keys for the key/val store.
     */
    public function __construct(DynamoDbClient $client, Marshaler $marshaler = null, array $options = [])
    {
        $this->client = $client;
        $this->marshaler = $marshaler ?: new Marshaler();
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Retrieves a specific name for a key for a given storage name defaulting to 'default_key_name' option.
     *
     * @param string $storageName The name of the storage (i.e. table).
     *
     * @return string
     */
    protected function getKeyNameForStorage($storageName)
    {
        return isset($this->options['storage_keys'][$storageName]) ?
            $this->options['storage_keys'][$storageName] :
            $this->options['default_key_name'];
    }

    /**
     * Prepares a key to be in a valid format for lookups for DynamoDB. If passing an array, that means that the key
     * is the name of the key and the value is the actual value for the lookup.
     *
     * @param string $storageName
     *
     * @return array The key in DynamoDB format.
     */
    protected function prepareKey($storageName, $key)
    {
        if (is_array($key)) {
            $keyValue = reset($key);
            $keyName = key($key);
        } else {
            $keyValue = $key;
            $keyName = $this->getKeyNameForStorage($storageName);
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
        return 'amazondynamodb';
    }
}
