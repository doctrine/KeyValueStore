<?php
namespace Doctrine\Tests\KeyValueStore\Storage;

abstract class AbstractStorageTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @return \Doctrine\KeyValueStore\Storage\Storage
     */
    abstract protected function createStorage();

    public function setUp()
    {
        $this->storage = $this->createStorage();
    }

    public function testInsertThenFindCompositeKey()
    {
        if ( ! $this->storage->supportsCompositeKeys()) {
            $this->markTestSkipped("Composite keys need to be supported for this test to run.");
        }

        $key = array('dist' => 'foo', 'range' => 100);
        $data = array('dist' => 'foo', 'range' => 100, 'name' => 'Test', 'value' => 'val', 'date' => new \DateTime("2012-03-26 12:12:12"));

        $this->storage->insert($key, $data);
        $foundData = $this->storage->find($key);

        $this->assertEquals($data, $foundData);
    }
}

