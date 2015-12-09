<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\CouchDbStorage;

/**
 * CouchDb storage testcase
 *
 * @author Emanuele Minotto <minottoemanuele@gmail.com>
 *
 * @covers \Doctrine\KeyValueStore\Storage\CouchDbStorage
 */
class CouchDbStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $couchdb;

    /**
     * @var CouchDbStorage
     */
    private $storage;

    protected function setUp()
    {
        $client = $this->getMockBuilder('\Doctrine\CouchDB\HTTP\StreamClient')
            ->disableOriginalConstructor()
            ->getMock();

        $this->couchdb = $this->getMockBuilder('\Doctrine\CouchDB\CouchDBClient')
            ->setConstructorArgs(array(
                $client,
                'test',
            ))
            ->getMock();

        $this->storage = new CouchDbStorage($this->couchdb);
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
        $data = range(0, 10);

        $storedDataset = null;

        $this->couchdb->expects($this->once())
            ->method('putDocument')
            ->will($this->returnCallback(function(array $data, $id) use (&$storedDataset) {
                $storedDataset = array($id, null);
            }));

        $storageName = rand();
        $key = sha1(rand());

        $this->storage->insert($storageName, $key, $data);
        $this->assertNotNull($storedDataset);

        $this->assertSame(array($storageName.'-'.$key, null), $storedDataset);
    }

    public function testUpdate()
    {
        $data = range(0, 10);

        $storedDataset = null;

        $this->couchdb->method('putDocument')
            ->will($this->returnCallback(function(array $data, $id) use (&$storedDataset) {
                $storedDataset = array($id, null);
            }));

        $storageName = rand();
        $key = sha1(rand());

        $this->storage->insert($storageName, $key, $data);
        $this->assertNotNull($storedDataset);

        $this->assertSame(array($storageName.'-'.$key, null), $storedDataset);

        $data = range(0, 20);

        $this->storage->insert($storageName, $key, $data);
        $this->assertNotNull($storedDataset);

        $this->assertSame(array($storageName.'-'.$key, null), $storedDataset);
    }

    public function testDelete()
    {
        $storedDataset = array(
            'test-foobar' => array(
                'author' => 'John Doe',
                'title'  => 'example book',
            ),
        );

        $this->couchdb->expects($this->once())
             ->method('deleteDocument')
             ->will($this->returnCallback(function($key) use (&$storedDataset) {
                    foreach ($storedDataset as $id => $row) {
                        if ($id === $key) {
                            unset($storedDataset[$key]);
                        }
                    }
                }
             ));

        $this->storage->delete('test', 'foobar');

        $this->assertCount(0, $storedDataset);
    }

    public function testFind()
    {
        $storedDataset = array(
            'test-foobar' => array(
                'author' => 'John Doe',
                'title'  => 'example book',
            ),
        );

        $this->couchdb->expects($this->once())
            ->method('findDocument')
            ->will($this->returnCallback(function($key) use (&$storedDataset) {
                if (isset($storedDataset[$key])) {
                    return $storedDataset[$key];
                }

                return null;
            }
        ));

        $data = $this->storage->find('test', 'foobar');

        $this->assertEquals($storedDataset['test-foobar'], $data);
    }

    public function testGetName()
    {
        $this->assertEquals('couchdb', $this->storage->getName());
    }
}
