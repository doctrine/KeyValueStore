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

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\Storage\MongoDbStorage;
use MongoDB\Client;

/**
 * MongoDb storage testcase
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 *
 * @covers \Doctrine\KeyValueStore\Storage\MongoDbStorage
 * @requires extension mongodb
 */
class MongoDbStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var MongoDbStorage
     */
    private $storage;

    protected function setUp()
    {
        $this->client = new Client();
        $this->storage = new MongoDbStorage($this->client->test);
    }

    public function testInsert()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('mongodb', 'testInsert', $data);

        $result = $this->client
            ->test
            ->mongodb
            ->findOne([
                'key' => 'testInsert',
            ]);

        $this->assertSame($data, $result['value']->getArrayCopy());
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('mongodb', 'testUpdate', [
            'foo' => 'bar',
        ]);
        $this->storage->update('mongodb', 'testUpdate', $data);

        $result = $this->client
            ->test
            ->mongodb
            ->findOne([
                'key' => 'testUpdate',
            ]);

        $this->assertSame($data, $result['value']->getArrayCopy());
    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        $this->storage->insert('mongodb', 'testDelete', [
            'foo' => 'bar',
        ]);

        $this->storage->delete('mongodb', 'testDelete');

        $result = $this->client
            ->test
            ->mongodb
            ->findOne([
                'key' => 'testDelete',
            ]);

        $this->assertNull($result);
    }

    /**
     * @depends testInsert
     */
    public function testFind()
    {
        $dataset = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('mongodb', 'testFind', $dataset);

        $data = $this->storage->find('mongodb', 'testFind');

        $this->assertEquals($dataset, $data);
    }

    public function testFindWithNotExistKey()
    {
        $this->setExpectedException(NotFoundException::class);
        $this->storage->find('mongodb', 'not-existing-key');
    }

    public function testGetName()
    {
        $this->assertEquals('mongodb', $this->storage->getName());
    }
}
