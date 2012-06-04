<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\MongoDbStorage;

/**
 * MongoDb storage testcase
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MongoDbStorageTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if ( ! class_exists('Mongo')) {
            $this->markTestSkipped('Mongo needs to be installed');
        }

        $this->mongo = $this->getMock('\Mongo');

        $this->mongodb = $this->getMockBuilder('\MongoDB')->disableOriginalConstructor()->getMock();

        $this->mongo->expects($this->any())
                    ->method('selectDB')
                    ->will($this->returnValue($this->mongodb));

        $this->collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();

        $this->mongodb->expects($this->once())
             ->method('selectCollection')
             ->will($this->returnValue($this->collection));

        $this->storage = new MongoDbStorage($this->mongo, array(
            'collection' => 'test',
            'database' => 'test'
        ));
    }

    public function testInsert()
    {
        $data = array(
            'author' => 'John Doe',
            'title'  => 'example book',
        );

        $dbDataset = array();

        $this->collection->expects($this->once())
            ->method('insert')
            ->will($this->returnCallback(function($data) use (&$dbDataset) {
                $dbDataset[] = $data;
            }));

        $this->storage->insert('mongodb', '1', $data);
        $this->assertCount(1, $dbDataset);

        $this->assertEquals(array(array('key' => '1', 'value' => $data)), $dbDataset);
    }

    public function testUpdate()
    {
        $data = array(
            'author' => 'John Doe',
            'title'  => 'example book',
        );

        $dbDataset = array();

        $this->collection->expects($this->once())
            ->method('update')
            ->will($this->returnCallback(function($citeria, $data) use (&$dbDataset) {
                $dbDataset = array($citeria, $data);
            }));

        $this->storage->update('mongodb', '1', $data);

        $this->assertEquals(array('key' => '1'), $dbDataset[0]);
        $this->assertEquals(array('key' => '1', 'value' => $data), $dbDataset[1]);
    }

    public function testDelete()
    {
        $dataset = array(
            array(
                'key' => 'foobar',
                'value' => array(
                    'author' => 'John Doe',
                    'title'  => 'example book',
                ),
            ),
        );

        $this->collection->expects($this->once())
             ->method('remove')
             ->will($this->returnCallback(function($citeria) use (&$dataset) {
                    foreach ($dataset as $key => $row) {
                        if ($row['key'] === $citeria['key']) {
                            unset($dataset[$key]);
                        }
                    }
                }
             ));

        $this->storage->delete('test', 'foobar');

        $this->assertCount(0, $dataset);
    }

    public function testFind()
    {
        $dataset = array(
            array(
                'key' => 'foobar',
                'value' => array(
                    'author' => 'John Doe',
                    'title'  => 'example book',
                ),
            ),
        );

        $this->collection->expects($this->once())
            ->method('findOne')
            ->will($this->returnCallback(function($citeria, $fields) use (&$dataset) {
                foreach ($dataset as $key => $row) {
                    if ($row['key'] === $citeria['key']) {
                        return $row;
                    }
                }
            }
        ));

        $data = $this->storage->find('test', 'foobar');

        $this->assertEquals($dataset[0]['value'], $data);
    }

    public function testGetName()
    {
        $this->storage->initialize();

        $this->assertEquals('mongodb', $this->storage->getName());
    }
}
