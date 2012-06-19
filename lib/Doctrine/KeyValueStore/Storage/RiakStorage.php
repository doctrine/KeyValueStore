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

use Doctrine\KeyValueStore\NotFoundException;

use Riak\Client;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class RiakStorage implements Storage
{
    /**
     * @var \Riak\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $bucketName;

    /**
     * Constructor
     *
     * @param \Riak\Client $riak
     * @param string       $bucketName
     */
    public function __construct(Client $riak, $bucketName)
    {
        $this->client = $riak;
        $this->bucketName = $bucketName;
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
        return false;
    }

    /**
     * Does this storage support composite primary keys?
     *
     * @return bool
     */
    function supportsCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * Does this storage require composite primary keys?
     *
     * @return bool
     */
    function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * Insert data into the storage key specified.
     *
     * @param string $storageName
     * @param array|string $key
     * @param array $data
     * @return void
     */
    public function insert($storageName, $key, array $data)
    {
        $bucket = $this->client->bucket($this->bucketName);
        $object = $bucket->newObject($key, $data);
        $object->store();
    }

    /**
     * Update data into the given key.
     *
     * @param string $storageName
     * @param array|string $key
     * @param array $data
     * @return void
     */
    public function update($storageName, $key, array $data)
    {
        $bucket = $this->client->bucket($this->bucketName);
        /** @var $object \Riak\Object */
        $object = $bucket->get($key);

        $object->setData($data);
        $object->store();
    }

    /**
     * Delete data at key
     *
     * @param string $storageName
     * @param array|string $key
     * @return void
     */
    public function delete($storageName, $key)
    {
        $bucket = $this->client->bucket($this->bucketName);

        /** @var $object \Riak\Object */
        $object = $bucket->get($key);

        if (!$object->exists()) {
            // object does not exist, do nothing
            return;
        }

        $object->delete();
    }

    /**
     * Find data at key
     *
     * Important note: The returned array does contain the identifier (again)!
     *
     * @throws Doctrine\KeyValueStore\NotFoundException When data with key is not found.
     *
     * @param string $storageName
     * @param array|string $key
     * @return array
     */
    public function find($storageName, $key)
    {
        $bucket = $this->client->bucket($this->bucketName);

        /** @var $object \Riak\Object */
        $object = $bucket->get($key);

        if (!$object->exists()) {
            throw new NotFoundException;
        }

        return $object->getData();
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'riak';
    }
}
