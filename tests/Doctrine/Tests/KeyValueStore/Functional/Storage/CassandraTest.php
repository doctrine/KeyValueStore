<?php

namespace Doctrine\Tests\KeyValueStore\Functional\Storage;

use Cassandra;
use Doctrine\KeyValueStore\Storage\CassandraStorage;

class CassandraTest extends \PHPUnit_Framework_TestCase
{
    private $session;
    private $storage;

    protected function setUp()
    {
        if ( ! extension_loaded('cassandra')) {
            $this->markTestSkipped('Cassandra Extension is not installed.');
        }

        $cluster = Cassandra::cluster()->build();
        $this->session = $cluster->connect();

        try {
            $this->session->execute(new \Cassandra\SimpleStatement("DROP KEYSPACE doctrine"));
        } catch (\Cassandra\Exception\RuntimeException $e) {
        }

        $cql = "CREATE KEYSPACE doctrine WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 1 };";
        $statement = new \Cassandra\SimpleStatement($cql);
        $this->session->execute($statement);

        $this->session->execute(new \Cassandra\SimpleStatement('USE doctrine'));

        $cql = "CREATE TABLE books (id int, author text, title text, PRIMARY KEY (id));";

        $this->session->execute(new \Cassandra\SimpleStatement($cql));
        $this->storage = new CassandraStorage($this->session);
    }

    public function testInsert()
    {
        $data = array(
            'author' => 'John Doe',
            'title'  => 'example book',
        );

        $this->storage->insert('books', array('id' => 1), $data);

        $cql = "SELECT * FROM books WHERE id = 1";
        $result = $this->session->execute(new \Cassandra\SimpleStatement($cql));
        $rows = iterator_to_array($result);

        $this->assertEquals("1", $rows[0]['id']);
        $this->assertEquals("John Doe", $rows[0]['author']);
        $this->assertEquals("example book", $rows[0]['title']);
    }

    public function testFind()
    {
        $data = array(
            'author' => 'John Doe',
            'title'  => 'example book',
        );

        $this->storage->insert('books', array('id' => 2), $data);

        $this->assertEquals($data, $this->storage->find('books', array('id' => 2)));
    }

    public function testUpdate()
    {
        $data = array(
            'author' => 'John Doe',
            'title'  => 'example book',
        );

        $this->storage->insert('books', array('id' => 3), $data);
        $this->storage->update('books', array('id' => 3), array('author' => 'Jane Doe'));

        $this->assertEquals(
            array('author' => 'Jane Doe', 'title' => 'example book'),
            $this->storage->find('books', array('id' => 3))
        );
    }

    public function testDelete()
    {
        $data = array(
            'author' => 'John Doe',
            'title'  => 'example book',
        );

        $this->storage->insert('books', array('id' => 4), $data);
        $this->storage->delete('books', array('id' => 4));

        $this->setExpectedException('Doctrine\KeyValueStore\NotFoundException');

        $this->storage->find('books', array('id' => 4));
    }
}
