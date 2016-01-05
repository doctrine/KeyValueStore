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

use Doctrine\KeyValueStore\Exception\NotFoundException;
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
     * Constructor
     *
     * @param \Riak\Client $riak
     * @param string       $bucketName
     */
    public function __construct(Client $riak)
    {
        $this->client = $riak;
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
        $bucket = $this->client->bucket($storageName);
        $object = $bucket->newObject($key, $data);
        $object->store();
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $bucket = $this->client->bucket($storageName);
        /** @var $object \Riak\Object */
        $object = $bucket->get($key);

        $object->setData($data);
        $object->store();
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $bucket = $this->client->bucket($storageName);

        /** @var $object \Riak\Object */
        $object = $bucket->get($key);

        if (! $object->exists()) {
            // object does not exist, do nothing
            return;
        }

        $object->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $bucket = $this->client->bucket($storageName);

        /** @var $object \Riak\Object */
        $object = $bucket->get($key);

        if (! $object->exists()) {
            throw new NotFoundException;
        }

        return $object->getData();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'riak';
    }
}
