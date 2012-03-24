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
 * and is licensed under the LGPL. For more information, see
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

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function supportsPartialUpdates()
    {
        return false;
    }

    public function supportsCompositePrimaryKeys()
    {
        return true;
    }

    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    private function flattenKey(array $key)
    {
        $hash = "oid:";
        ksort($key);
        foreach ($key as $property => $value) {
            $hash .= "$property=$value;";
        }
        return $hash;
    }

    public function insert(array $key, array $data)
    {
        $key = $this->flattenKey($key);
        $this->cache->save($key, $data);
    }

    public function update(array $key, array $data)
    {
        $key = $this->flattenKey($key);
        $this->cache->save($key, $data);
    }

    public function delete(array $key)
    {
        $key = $this->flattenKey($key);
        $this->cache->delete($key);
    }

    public function find(array $key)
    {
        $key = $this->flattenKey($key);
        return $this->cache->fetch($key);
    }

    public function getName()
    {
        return 'doctrine_cache';
    }
}

