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

use PHPUnit_Framework_TestCase;
use Riak\Bucket;
use Riak\Client;
use Riak\Object;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Storage\RiakStorage
 */
class RiakStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var RiakStorage
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->client = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new RiakStorage($this->client);
    }

    /**
     * @covers ::supportsPartialUpdates
     */
    public function testSupportsPartialUpdates()
    {
        $this->assertFalse($this->object->supportsPartialUpdates());
    }

    /**
     * @covers ::supportsCompositePrimaryKeys
     */
    public function testSupportsCompositePrimaryKeys()
    {
        $this->assertFalse($this->object->supportsCompositePrimaryKeys());
    }

    /**
     * @covers ::requiresCompositePrimaryKeys
     */
    public function testRequiresCompositePrimaryKeys()
    {
        $this->assertFalse($this->object->requiresCompositePrimaryKeys());
    }

    /**
     * @covers ::insert
     */
    public function testInsert()
    {
        $bucket = $this
            ->getMockBuilder(Bucket::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client
            ->expects($this->once())
            ->method('bucket')
            ->willReturn($bucket);

        $objectMock = $this
            ->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock
            ->expects($this->once())
            ->method('store');

        $bucket
            ->expects($this->once())
            ->method('newObject')
            ->will($this->returnCallback(function ($key, $data) use ($objectMock) {
                $this->assertEquals('foobar', $key);
                $this->assertEquals(['title' => 'Riak test'], $data);

                return $objectMock;
            }));

        $this->object->insert('riak-test', 'foobar', ['title' => 'Riak test']);
    }

    /**
     * @covers ::update
     */
    public function testUpdate()
    {
        $objectMock = $this
            ->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this
            ->getMockBuilder(Bucket::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client
            ->expects($this->once())
            ->method('bucket')
            ->willReturn($bucket);

        $bucket
            ->expects($this->once())
            ->method('get')
            ->willReturn($objectMock);

        $objectMock
            ->expects($this->once())
            ->method('setData')
            ->will($this->returnCallback(function ($data) {
                $this->assertEquals(['title' => 'Riak cookbook'], $data);
            }));

        $objectMock
            ->expects($this->once())
            ->method('store');

        $this->object->update('riak-test', 'foobar', ['title' => 'Riak cookbook']);
    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        $objectMock = $this
            ->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this
            ->getMockBuilder(Bucket::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client
            ->expects($this->once())
            ->method('bucket')
            ->willReturn($bucket);

        $bucket
            ->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->willReturn($objectMock);

        $objectMock
            ->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $objectMock
            ->expects($this->once())
            ->method('delete');

        $this->object->delete('riak-test', 'foobar');
    }

    /**
     * @covers ::delete
     */
    public function testDeleteWithNotExistKey()
    {
        $objectMock = $this
            ->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this
            ->getMockBuilder(Bucket::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client
            ->expects($this->once())
            ->method('bucket')
            ->willReturn($bucket);

        $bucket
            ->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->willReturn($objectMock);

        $objectMock
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $objectMock
            ->expects($this->never())
            ->method('delete');

        $this->object->delete('riak-test', 'foobar');
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        $objectMock = $this
            ->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this
            ->getMockBuilder(Bucket::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client
            ->expects($this->once())
            ->method('bucket')
            ->willReturn($bucket);

        $bucket
            ->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->willReturn($objectMock);

        $objectMock
            ->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $objectMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn(['title' => 'Riak Test']);

        $this->assertEquals(['title' => 'Riak Test'], $this->object->find('riaktest', 'foobar'));
    }

    /**
     * @covers ::find
     * @expectedException Doctrine\KeyValueStore\NotFoundException
     */
    public function testFindWithNotExistKey()
    {
        $objectMock = $this
            ->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this
            ->getMockBuilder(Bucket::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->client
            ->expects($this->once())
            ->method('bucket')
            ->willReturn($bucket);

        $bucket
            ->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->willReturn($objectMock);

        $objectMock
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $objectMock
            ->expects($this->never())
            ->method('getData');

        $this->object->find('riak-test', 'foobar');
    }

    /**
     * @covers ::getName
     */
    public function testGetName()
    {
        $this->assertEquals('riak', $this->object->getName());
    }
}
