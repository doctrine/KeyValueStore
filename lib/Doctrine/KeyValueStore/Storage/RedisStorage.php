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

/**
 * @author Marcel Araujo <admin@marcelaraujo.me>
 */
class RedisStorage implements Storage
{
    /**
     * @var \Redis
     */
    protected $client;

    /**
     * @var array
     */
    protected $dbOptions;

    /**
     * Redis Key Prefix
     *
     * @var string
     */
    protected $keyPrefix = 'doctrine:storage:';

    /**
     * Constructor
     *
     * @param \Redis $redis
     * @param array $dbOptions
     */
    public function __construct($redis, $dbOptions = array())
    {
        $this->client = $redis;

        $this->dbOptions = array_merge(array(
            'keyPrefix' => $this->keyPrefix
        ), $dbOptions);
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
        $this->client->set($this->getKeyName($key), json_encode($data));
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $this->client->set($this->getKeyName($key), json_encode($data));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $key = $this->getKeyName($key);

        if ($this->client->exists($key)) {
            $this->client->delete($key);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $key = $this->getKeyName($key);

        if (! $this->client->exists($key)) {
            throw new NotFoundException();
        }

        return json_decode($this->client->get($key), true);
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'redis';
    }

    /**
     * Add prefix to Redis key space name
     *
     * @param  string $key
     * @return string
     */
    public function getKeyName($key)
    {
        return $this->keyPrefix . $key;
    }
}
