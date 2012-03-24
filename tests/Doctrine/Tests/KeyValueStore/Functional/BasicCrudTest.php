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

namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\KeyValueStore\EntityManager;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
use Doctrine\KeyValueStore\Mapping\AnnotationDriver;
use Doctrine\KeyValueStore\Mapping\Annotations as KVS;

class BasicCrudTest extends \PHPUnit_Framework_TestCase
{
    private $manager;
    private $cache;

    public function setUp()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $metadata = new AnnotationDriver($reader);
        $cache = new ArrayCache();
        $storage = new DoctrineCacheStorage($cache);
        $this->manager = new EntityManager($storage, $cache, $metadata);
        $this->cache = $cache;
    }

    public function testPersistItem()
    {
        $post = new Post();
        $post->id = "1";
        $post->headline = "asdf";
        $post->text = "foo";

        $this->manager->persist($post);
        $this->manager->flush();

        $this->assertTrue($this->cache->contains("oid:id=1;"));
    }

    public function testPersistAndRetrieveItem()
    {
        $post = new Post();
        $post->id = "1";
        $post->headline = "asdf";
        $post->text = "foo";

        $this->manager->persist($post);
        $this->manager->flush();

        $post2 = $this->manager->find(__NAMESPACE__ . '\\Post', 1);
        $this->assertSame($post, $post2);
    }

    public function testRetrieveItem()
    {
        $this->cache->save("oid:id=1;", array('id' => 1, 'headline' => 'test', 'body' => 'tset', 'foo' => 'bar', 'php_class' => __NAMESPACE__ . '\\Post'));

        $post = $this->manager->find(__NAMESPACE__ . '\\Post', 1);

        $this->assertEquals('test', $post->headline);
        $this->assertEquals('tset', $post->body);
        $this->assertEquals('bar', $post->foo);

        $post2 = $this->manager->find(__NAMESPACE__ . '\\Post', 1);
        $this->assertSame($post, $post2);
    }

    public function testRetrieveWrongClass()
    {
        $this->cache->save("oid:id=1;", array('id' => 1, 'headline' => 'test', 'body' => 'tset', 'foo' => 'bar', 'php_class' => 'stdClass'));

        $this->setExpectedException("RuntimeException", "Trying to reconstitute");
        $post = $this->manager->find(__NAMESPACE__ . '\\Post', 1);
    }

    public function testUpdateClass()
    {
        $post = new Post();
        $post->id = "1";
        $post->headline = "asdf";
        $post->text = "foo";

        $this->manager->persist($post);
        $this->manager->flush();

        $post->body = "bar";
        $post->text = "baz";

        $this->manager->flush();

        $this->assertEquals(array('id' => 1, 'headline' => 'asdf', 'body' => 'bar', 'text' => 'baz', 'php_class' => __NAMESPACE__ . '\\Post'), $this->cache->fetch("oid:id=1;"));
    }

    public function testRemoveClass()
    {
        $post = new Post();
        $post->id = "1";
        $post->headline = "asdf";
        $post->text = "foo";

        $this->manager->persist($post);
        $this->manager->flush();

        $this->assertTrue($this->cache->contains('oid:id=1;'));

        $this->manager->remove($post);
        $this->manager->flush();

        $this->assertFalse($this->cache->contains('oid:id=1;'));
    }
}

/**
 * @KVS\Entity
 */
class Post
{
    /** @KVS\Id */
    public $id;
    public $headline;
    public $body;

}
