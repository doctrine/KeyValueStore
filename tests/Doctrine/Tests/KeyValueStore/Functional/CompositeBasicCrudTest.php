<?php
namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;

class CompositeBasicCrudTest extends BasicCrudTestCase
{
    private $cache;

    protected function createStorage()
    {
        $this->cache = new ArrayCache();
        $storage = new DoctrineCacheStorage($this->cache);
        return $storage;
    }

    public function assertKeyExists($id)
    {
        $this->assertTrue($this->cache->contains("post-oid:id=".$id.";"));
    }

    public function assertKeyNotExists($id)
    {
        $this->assertFalse($this->cache->contains("post-oid:id=".$id.";"));
    }

    public function populate($id, array $data)
    {
        $this->cache->save("post-oid:id=".$id.";", $data);
    }

    public function find($id)
    {
        return $this->cache->fetch("post-oid:id=".$id.";");
    }
}

