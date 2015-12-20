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

namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\KeyValueStore\Mapping\Annotations as KVS;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
use Doctrine\Tests\KeyValueStoreTestCase;

/**
 * @group legacy
 */
class InheritanceTest extends KeyValueStoreTestCase
{
    private $manager;
    protected $storage;

    /**
     * @dataProvider mappingDrivers
     */
    public function testInheritance($mappingDriver)
    {
        $cache         = new ArrayCache();
        $storage       = new DoctrineCacheStorage($cache);
        $this->manager = $this->createManager($storage, $mappingDriver);

        $parent      = new ParentEntity;
        $parent->id  = 1;
        $parent->foo = 'foo';
        $this->manager->persist($parent);

        $child      = new ChildEntity;
        $child->id  = 2;
        $child->foo = 'bar';
        $child->bar = 'baz';

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

    public function mappingDrivers()
    {
        return [
            ['annotation'],
            ['yaml'],
            ['xml'],
        ];
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
