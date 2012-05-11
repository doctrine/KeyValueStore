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

use Doctrine\Common\Cache\Cache;

/**
 * Key-Value-Storage using a Doctrine Cache as backend.
 *
 * Given the nature of caches, data inside this storage is not "persistent"
 * but depends on the garbage collection of the underlying storage.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DoctrineCacheStorage implements Storage
{
    /**
     * @var Doctrine\Common\Cache\Cache
     */
    private $cache;

    private $supportsCompositeKeys;

    public function __construct(Cache $cache, $supportsCompositeKeys = true)
    {
        $this->cache = $cache;
        $this->supportsCompositeKeys = $supportsCompositeKeys;
    }

    public function supportsPartialUpdates()
    {
        return false;
    }

    public function supportsCompositePrimaryKeys()
    {
        return $this->supportsCompositeKeys;
    }

    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    private function flattenKey($storageName, $key)
    {
        if ( ! $this->supportsCompositeKeys) {
            return $storageName . "-" . $key;
        }

        $hash = $storageName . "-oid:";
        ksort($key);
        foreach ($key as $property => $value) {
            $hash .= "$property=$value;";
        }
        return $hash;
    }

    public function insert($storageName, $key, array $data)
    {
        $key = $this->flattenKey($storageName, $key);
        $this->cache->save($key, $data);
    }

    public function update($storageName, $key, array $data)
    {
        $key = $this->flattenKey($storageName, $key);
        $this->cache->save($key, $data);
    }

    public function delete($storageName, $key)
    {
        $key = $this->flattenKey($storageName, $key);
        $this->cache->delete($key);
    }

    public function find($storageName, $key)
    {
        $key = $this->flattenKey($storageName, $key);
        return $this->cache->fetch($key);
    }

    public function getName()
    {
        return 'doctrine_cache';
    }
}

