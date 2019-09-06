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
use Riak\Client\Command\Kv\DeleteValue;
use Riak\Client\Command\Kv\FetchValue;
use Riak\Client\Command\Kv\StoreValue;
use Riak\Client\Core\Query\RiakLocation;
use Riak\Client\Core\Query\RiakNamespace;
use Riak\Client\Core\Query\RiakObject;
use Riak\Client\RiakClient;
use Riak\Client\RiakException;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class RiakStorage implements Storage
{
    /**
     * @var RiakClient
     */
    private $client;

    public function __construct(RiakClient $riak)
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

    private function store($storageName, $key, array $data)
    {
        $location = $this->getRiakLocation($storageName, $key);

        $riakObject = new RiakObject();
        $riakObject->setContentType('application/json');
        $riakObject->setValue(json_encode($data));

        $store = StoreValue::builder($location, $riakObject)->build();

        $this->client->execute($store);
    }

    /**
     * {@inheritDoc}
     */
    public function insert($storageName, $key, array $data)
    {
        $this->store($storageName, $key, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $this->store($storageName, $key, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $location = $this->getRiakLocation($storageName, $key);

        $delete = DeleteValue::builder($location)->build();

        try {
            $this->client->execute($delete);
        } catch (RiakException $exception) {
            // deletion can fail silent
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $location = $this->getRiakLocation($storageName, $key);

        // fetch object
        $fetch = FetchValue::builder($location)->build();

        try {
            $result = $this->client->execute($fetch);
        } catch (RiakException $exception) {
            throw new NotFoundException();
        }

        $json = (string) $result
            ->getValue()
            ->getValue();

        return json_decode($json, true);
    }

    private function getRiakLocation($storageName, $key)
    {
        $namespace = new RiakNamespace('default', $storageName);

        return new RiakLocation($namespace, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'riak';
    }
}
