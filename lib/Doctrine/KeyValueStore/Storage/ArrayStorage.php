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

use Doctrine\DBAL\Connection;
use Doctrine\KeyValueStore\NotFoundException;

/**
 * Array storage, mainly used for development purposes.
 *
 * @author Emanuele Minotto <minottoemanuele@gmail.com>
 */
class ArrayStorage implements Storage
{
    /**
     * @var array
     */
    private $data = [];

    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * Does this storage support composite primary keys?
     *
     * @return bool
     */
    public function supportsCompositePrimaryKeys()
    {
        return false;
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
     * @param array|string $key
     * @param array        $data
     */
    public function insert($storageName, $key, array $data)
    {
        $this->update($storageName, $key, $data);
    }

    /**
     * Update data into the given key.
     *
     * @param array|string $key
     * @param array        $data
     */
    public function update($storageName, $key, array $data)
    {
        if (!isset($this->data[$storageName])) {
            $this->data[$storageName] = [];
        }

        $this->data[$storageName][serialize($key)] = $data;
    }

    /**
     * Delete data at key
     *
     * @param array|string $key
     */
    public function delete($storageName, $key)
    {
        if (!isset($this->data[$storageName])) {
            return;
        }

        if (!isset($this->data[$storageName][serialize($key)])) {
            return;
        }

        unset($this->data[$storageName][serialize($key)]);
    }

    /**
     * Find data at key
     *
     * @param array|string $key
     *
     * @return array
     */
    public function find($storageName, $key)
    {
        if (!isset($this->data[$storageName])) {
            throw new NotFoundException();
        }

        if (!isset($this->data[$storageName][serialize($key)])) {
            throw new NotFoundException();
        }

        unset($this->data[$storageName][serialize($key)]);
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'array';
    }
}
