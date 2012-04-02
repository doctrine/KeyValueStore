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

    public function testInsertCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped("Composite keys need to be supported for this test to run.");
        }

        $key = array('dist' => 'foo', 'range' => 100);
        $data = array(
            'dist' => 'foo',
            'range' => 100,
            'name' => 'Test',
            'value' => 1,
            'amount' => 200.23,
            'timestamp' => new \DateTime("2012-03-26 12:12:12")
        );

        $this->mockInsertCompositeKey($key, $data);
        $this->storage->insert('stdClass', $key, $data);
    }

    public function testUpdateCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped("Composite keys need to be supported for this test to run.");
        }

        $key = array('dist' => 'foo', 'range' => 100);
        $data = array(
            'dist' => 'foo',
            'range' => 100,
            'name' => 'Test',
            'value' => 1,
            'amount' => 200.23,
            'timestamp' => new \DateTime("2012-03-26 12:12:12")
        );

        $this->mockUpdateCompositeKey($key, $data);
        $this->storage->update('stdClass', $key, $data);
    }

    public function testDeleteCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped("Composite keys need to be supported for this test to run.");
        }

        $key = array('dist' => 'foo', 'range' => 100);

        $this->mockDeleteCompositeKey($key);
        $this->storage->delete('stdClass', $key);
    }

    public function testFindCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped("Composite keys need to be supported for this test to run.");
        }

        $key = array('dist' => 'foo', 'range' => 100);

        $this->mockFindCompositeKey($key);
        $data = $this->storage->find('stdClass', $key);

        $this->assertEquals(array(
            'dist' => 'foo',
            'range' => '100',
            'timestamp' => new \DateTime('2008-09-18 23:46:19', new \DateTimeZone("UTC")),
            'name' => 'Test',
            'value' => 23,
            'amount' => 200.23,
            'bool' => true,
        ), $data);
    }

    abstract function mockInsertCompositeKey($key, $data);
    abstract function mockUpdateCompositeKey($key, $data);
    abstract function mockDeleteCompositeKey($key);
    abstract function mockFindCompositeKey($key);
}

