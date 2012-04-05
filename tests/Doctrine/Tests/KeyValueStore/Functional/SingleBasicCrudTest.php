<?php
namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;

class SingleBasicCrudTest extends BasicCrudTestCase
{
    private $cache;

    protected function createStorage()
    {
        $this->cache = new ArrayCache();
        $storage = new DoctrineCacheStorage($this->cache, false);
        return $storage;
    }

    public function assertKeyExists($id)
    {
        $this->assertTrue($this->cache->contains("post-".$id));
    }

    public function assertKeyNotExists($id)
    {
        $this->assertFalse($this->cache->contains("post-".$id));
    }

    public function populate($id, array $data)
    {
        $this->cache->save("post-".$id, $data);
    }

    public function find($id)
    {
        return $this->storage->find('post', $id);
    }
}

