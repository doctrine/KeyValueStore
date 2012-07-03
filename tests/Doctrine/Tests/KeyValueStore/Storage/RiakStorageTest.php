<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\RiakStorage;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class RiakStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RiakStorage
     */
    private $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $riak;

    protected function setup()
    {
        $this->riak = $this->getMockBuilder('Riak\\Client')
            ->disableOriginalConstructor()
            ->getMock();


        $this->storage = new RiakStorage($this->riak);
    }

    public function testSupportsPartialUpdates()
    {
        $this->assertFalse($this->storage->supportsPartialUpdates());
    }

    public function testSupportsCompositePrimaryKeys()
    {
        $this->assertFalse($this->storage->supportsCompositePrimaryKeys());
    }

    public function testRequiresCompositePrimaryKeys()
    {
        $this->assertFalse($this->storage->requiresCompositePrimaryKeys());
    }

    public function testInsert()
    {
        $bucket = $this->getMockBuilder('Riak\Bucket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->riak->expects($this->once())
            ->method('bucket')
            ->will($this->returnValue($bucket));

        $objectMock = $this->getMockBuilder('Riak\Object')
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock->expects($this->once())
            ->method('store');


        $that = $this;
        $bucket->expects($this->once())
            ->method('newObject')
            ->will($this->returnCallback(function($key, $data) use ($objectMock, $that) {
                $that->assertEquals('foobar', $key);
                $that->assertEquals(array('title' => 'Riak test'), $data);
                return $objectMock;
            }));

        $this->storage->insert('riak-test', 'foobar', array('title' => 'Riak test'));
    }

    public function testUpdate()
    {
        $objectMock = $this->getMockBuilder('Riak\Object')
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this->getMockBuilder('Riak\Bucket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->riak->expects($this->once())
            ->method('bucket')
            ->will($this->returnValue($bucket));

        $bucket->expects($this->once())
             ->method('get')
             ->will($this->returnValue($objectMock));


        $that = $this;
        $objectMock->expects($this->once())
            ->method('setData')
            ->will($this->returnCallback(function($data) use ($that) {
                $that->assertEquals(array('title' => 'Riak cookbook'), $data);
            }));

        $objectMock->expects($this->once())
            ->method('store');

        $this->storage->update('riak-test', 'foobar', array('title' => 'Riak cookbook'));
    }

    public function testDelete()
    {
        $objectMock = $this->getMockBuilder('Riak\Object')
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this->getMockBuilder('Riak\Bucket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->riak->expects($this->once())
            ->method('bucket')
            ->will($this->returnValue($bucket));

        $bucket->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->will($this->returnValue($objectMock));

        $objectMock->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));

        $objectMock->expects($this->once())
            ->method('delete');

        $this->storage->delete('riak-test', 'foobar');
    }

    public function testDeleteWithNotExistKey()
    {
        $objectMock = $this->getMockBuilder('Riak\Object')
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this->getMockBuilder('Riak\Bucket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->riak->expects($this->once())
            ->method('bucket')
            ->will($this->returnValue($bucket));

        $bucket->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->will($this->returnValue($objectMock));

        $objectMock->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(false));

        $objectMock->expects($this->never())
            ->method('delete');

        $this->storage->delete('riak-test', 'foobar');
    }

    public function testFind()
    {
        $objectMock = $this->getMockBuilder('Riak\Object')
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this->getMockBuilder('Riak\Bucket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->riak->expects($this->once())
            ->method('bucket')
            ->will($this->returnValue($bucket));

        $bucket->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->will($this->returnValue($objectMock));

        $objectMock->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));

        $objectMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array('title' => 'Riak Test')));

        $this->assertEquals(array('title' => 'Riak Test'), $this->storage->find('riaktest', 'foobar'));
    }

    /**
     * @expectedException Doctrine\KeyValueStore\NotFoundException
     */
    public function testFindWithNotExistKey()
    {
        $objectMock = $this->getMockBuilder('Riak\Object')
            ->disableOriginalConstructor()
            ->getMock();

        $bucket = $this->getMockBuilder('Riak\Bucket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->riak->expects($this->once())
            ->method('bucket')
            ->will($this->returnValue($bucket));

        $bucket->expects($this->once())
            ->method('get')
            ->with('foobar')
            ->will($this->returnValue($objectMock));

        $objectMock->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(false));

        $objectMock->expects($this->never())
            ->method('getData');

        $this->storage->find('riak-test', 'foobar');
    }

    public function testGetName()
    {
        $this->assertEquals('riak', $this->storage->getName());
    }
}