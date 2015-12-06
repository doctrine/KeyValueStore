<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\RedisStorage;

/**
 * @author Marcel Araujo <admin@marcelaraujo.me>
 */
class RedisStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RedisStorage
     */
    private $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $redis;

    protected function setup()
    {
        if ( ! extension_loaded('redis')) {
            $this->markTestSkipped('Redis Extension is not installed.');
        }

        $this->redis = $this->getMockBuilder('\Redis')
            ->disableOriginalConstructor()
            ->getMock();

        $this->redis->expects($this->any())
            ->method('connect')
            ->with('127.0.0.1', '6379')
            ->will($this->returnValue(TRUE));

        $this->storage = new RedisStorage($this->redis);
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
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $dbDataset = [];

        $this->redis->expects($this->once())
            ->method('set')
            ->will($this->returnCallback(function($key, $data) use (&$dbDataset) {
                $dbDataset[] = ['key' => $key, 'value' => $data];
            }));

        $this->storage->insert('redis', '1', $data);

        $this->assertCount(1, $dbDataset);
        $this->assertEquals([['key' => $this->storage->getKeyName('1'), 'value' => json_encode($data)]], $dbDataset);
    }

    public function testUpdate()
    {

        $data = [
            'author' => 'John Doe Updated',
            'title'  => 'example book updated',
        ];

        $dbDataset = [];

        $this->redis->expects($this->once())
            ->method('set')
            ->will($this->returnCallback(function($key, $data) use (&$dbDataset) {
                $dbDataset[] = ['key' => $key, 'value' => $data];
            }));


         $this->storage->update('redis', '1', $data);

         $this->assertCount(1, $dbDataset);
         $this->assertEquals([['key' => $this->storage->getKeyName('1'), 'value' => json_encode($data)]], $dbDataset);
    }

    public function testGetName()
    {
        $this->assertEquals('redis', $this->storage->getName());
    }
}
