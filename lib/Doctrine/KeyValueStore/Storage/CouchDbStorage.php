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

use Doctrine\CouchDB\CouchDBClient;

/**
 * Key-Value-Storage using a Doctrine CouchDB Client library as backend.
 *
 * CouchDB requires scalar identifiers, so the `flattenKey` method is used
 * to flatten passed keys.
 *
 * @author Emanuele Minotto <minottoemanuele@gmail.com>
 */
final class CouchDbStorage implements Storage
{
    /**
     * @var CouchDBClient
     */
    private $client;

    /**
     * @param CouchDBClient $client
     */
    public function __construct(CouchDBClient $client)
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
        $key = $this->flattenKey($storageName, $key);
        $this->client->putDocument($data, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $key = $this->flattenKey($storageName, $key);
        $this->client->putDocument($data, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $key = $this->flattenKey($storageName, $key);
        $this->client->deleteDocument($key, null);
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $key = $this->flattenKey($storageName, $key);
        return $this->client->findDocument($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'couchdb';
    }

    /**
     * @param string       $storageName
     * @param array|string $key
     *
     * @return string
     */
    private function flattenKey($storageName, $key)
    {
        $finalKey = $storageName . '-';

        if (is_string($key)) {
            return $finalKey . $key;
        }

        if (! is_array($key)) {
            throw new Exception\InvalidArgumentException('The key should be a string or a flat array.');
        }

        foreach ($key as $property => $value) {
            $finalKey .= sprintf('%s:%s-', $property, $value);
        }

        return $finalKey;
    }
}
