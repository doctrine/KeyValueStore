<?php
namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\KeyValueStore\Mapping\Annotations as KVS;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Tests\KeyValueStoreTestCase;

class InheritanceTest extends KeyValueStoreTestCase
{
    private $manager;
    protected $storage;

    public function setUp()
    {
        $cache = new ArrayCache();
        $storage = new DoctrineCacheStorage($cache);
        $this->manager = $this->createManager($storage);
    }

    public function testInheritance()
    {
        $parent = new ParentEntity;
        $parent->id = 1;
        $parent->foo = "foo";
        $this->manager->persist($parent);

        $child = new ChildEntity;
        $child->id = 2;
        $child->foo = "bar";
        $child->bar = "baz";

        $this->manager->persist($child);
        $this->manager->flush();
        $this->manager->clear();

        $parent = $this->manager->find(__NAMESPACE__ . '\ParentEntity', 1);
        $this->assertInstanceOf(__NAMESPACE__ . '\ParentEntity', $parent);
        $this->assertEquals(1, $parent->id);
        $this->assertEquals('foo', $parent->foo);

        $child = $this->manager->find(__NAMESPACE__ . '\ParentEntity', 2);
        $this->assertInstanceOf(__NAMESPACE__ . '\ChildEntity', $child);
        $this->assertEquals(2, $child->id);
        $this->assertEquals('bar', $child->foo);
        $this->assertEquals('baz', $child->bar);
    }
}

/**
 * @KVS\Entity
 */
class ParentEntity
{
    /**
     * @KVS\Id
     */
    public $id;
    public $foo;
}

/**
 * @KVS\Entity
 */
class ChildEntity extends ParentEntity
{
    public $bar;
}
