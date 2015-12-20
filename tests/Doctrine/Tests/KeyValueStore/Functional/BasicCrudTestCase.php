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

use Doctrine\KeyValueStore\Mapping\Annotations as KVS;
use Doctrine\Tests\KeyValueStoreTestCase;

/**
 * @group legacy
 */
abstract class BasicCrudTestCase extends KeyValueStoreTestCase
{
    private $manager;
    protected $storage;

    public function setUp()
    {
        $this->storage = $this->createStorage();
    }

    abstract protected function createStorage();

    abstract protected function assertKeyExists($id);

    abstract protected function populate($id, array $data);

    abstract protected function find($id);

    /**
     * @dataProvider mappingDrivers
     */
    public function testPersistItem($mappingDriver)
    {
        $this->manager = $this->createManager($this->storage, $mappingDriver);

        $post           = new Post();
        $post->id       = '1';
        $post->headline = 'asdf';
        $post->text     = 'foo';

        $this->manager->persist($post);
        $this->manager->flush();

        $this->assertKeyExists($post->id);
    }

    /**
     * @dataProvider mappingDrivers
     */
    public function testPersistAndRetrieveItem($mappingDriver)
    {
        $this->manager = $this->createManager($this->storage, $mappingDriver);

        $post           = new Post();
        $post->id       = '1';
        $post->headline = 'asdf';
        $post->text     = 'foo';

        $this->manager->persist($post);
        $this->manager->flush();

        $post2 = $this->manager->find(__NAMESPACE__ . '\\Post', 1);
        $this->assertSame($post, $post2);
    }

    /**
     * @dataProvider mappingDrivers
     */
    public function testRetrieveItem($mappingDriver)
    {
        $this->manager = $this->createManager($this->storage, $mappingDriver);

        $this->populate(1, ['id' => 1, 'headline' => 'test', 'body' => 'tset', 'foo' => 'bar', 'php_class' => __NAMESPACE__ . '\\Post']);

        $post = $this->manager->find(__NAMESPACE__ . '\\Post', 1);

        $this->assertEquals('test', $post->headline);
        $this->assertEquals('tset', $post->body);
        $this->assertEquals('bar', $post->foo);

        $post2 = $this->manager->find(__NAMESPACE__ . '\\Post', 1);
        $this->assertSame($post, $post2);
    }

    /**
     * @dataProvider mappingDrivers
     */
    public function testUpdateClass($mappingDriver)
    {
        $this->manager = $this->createManager($this->storage, $mappingDriver);

        $post           = new Post();
        $post->id       = '1';
        $post->headline = 'asdf';
        $post->text     = 'foo';

        $this->manager->persist($post);
        $this->manager->flush();

        $post->body = 'bar';
        $post->text = 'baz';

        $this->manager->flush();

        $this->assertEquals(
            [
                'id'        => 1,
                'headline'  => 'asdf',
                'body'      => 'bar',
                'text'      => 'baz',
                'php_class' => __NAMESPACE__ . '\\Post',
            ],
            $this->find(1)
        );
    }

    /**
     * @dataProvider mappingDrivers
     */
    public function testRemoveClass($mappingDriver)
    {
        $this->manager = $this->createManager($this->storage, $mappingDriver);

        $post           = new Post();
        $post->id       = '1';
        $post->headline = 'asdf';
        $post->text     = 'foo';

        $this->manager->persist($post);
        $this->manager->flush();

        $this->assertKeyExists($post->id);

        $this->manager->remove($post);
        $this->manager->flush();

        $this->assertKeyNotExists($post->id);
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
 * @KVS\Entity(storageName="post")
 */
class Post
{
    /** @KVS\Id */
    public $id;
    public $headline;
    public $body;
}
