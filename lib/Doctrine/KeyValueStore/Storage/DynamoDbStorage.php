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
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\KeyValueStore\NotFoundException;

/**
 * DynamoDb storage.
 *
 * @author Stan Lemon <stosh1985@gmail.com>
 */
class DynamoDbStorage implements Storage
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
     * @var Cache
     */
    private $descriptionCache;

    /**
     * @param DynamoDbClient $client           The client for connecting to AWS DynamoDB
     * @param Marshaler|null $marshaler        (optional) Marshaller for converting data to/from DynamoDB format
     * @param Cache|null     $descriptionCache Cache used to store tables description
     */
    public function __construct(
        DynamoDbClient $client,
        Marshaler $marshaler = null,
        Cache $descriptionCache = null
    ) {
        $this->client = $client;
        $this->marshaler = $marshaler ?: new Marshaler();
        $this->descriptionCache = $descriptionCache ?: new ArrayCache();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * Prepares a key to be in a valid format for lookups for DynamoDB. If passing an array, that means that the key
     * is the name of the key and the value is the actual value for the lookup.
     *
     * @param string       $storageName Table name
     * @param array|string $key         Key name
     *
     * @return array The key in DynamoDB format
     */
    private function prepareKey($storageName, $key)
    {
        if (! $this->descriptionCache->contains($storageName)) {
            $result = $this->client->describeTable([
                'TableName' => $storageName,
            ]);

            $keys = isset($result['Table']['KeySchema'])
                ? $result['Table']['KeySchema']
                : [];
            $keys = array_column($keys, 'AttributeName') ?: [];

            $this->descriptionCache->save($storageName, $keys);
        }

        $keys = isset($keys) ? $keys : $this->descriptionCache->fetch($storageName);
        $keys = array_combine($keys, array_fill(0, (count($keys) - 1) ?: 1, $key));

        if (!is_array($key)) {
            $key = [
                $storageName => $key,
            ];
        }

        $keys = array_intersect_assoc($keys, $key) ?: $keys;

        return $this->marshaler->marshalItem($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($storageName, $key, array $data)
    {
        $this->client->putItem([
            'TableName' => $storageName,
            'Item' => $this->prepareKey($storageName, $key) + $this->marshaler->marshalItem($data),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function update($storageName, $key, array $data)
    {
        // we are using PUT so we just replace the original item, if the key
        // does not exist, it will be created.
        $this->insert($storageName, $key, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($storageName, $key)
    {
        $this->client->deleteItem([
            'Key' => $this->prepareKey($storageName, $key),
            'TableName' => $storageName,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function find($storageName, $key)
    {
        $keys = $this->prepareKey($storageName, $key);

        $item = $this->client->getItem([
            'ConsistentRead' => true,
            'Key' => $keys,
            'TableName' => $storageName,
        ]);

        if (! $item->hasKey('Item')) {
            throw NotFoundException::notFoundByKey($key);
        }

        $item = $item->get('Item');

        $result = $this->marshaler->unmarshalItem($item);
        $result = array_diff_key($result, $keys);

        return $result;
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
}
