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

namespace Doctrine\Tests\KeyValueStore\Functional\Storage;

use Cassandra;
use Doctrine\KeyValueStore\Storage\CassandraStorage;

/**
 * @group legacy
 * @requires extension cassandra
 */
class CassandraTest extends \PHPUnit_Framework_TestCase
{
    private $session;
    private $storage;

    protected function setUp()
    {
        $cluster       = Cassandra::cluster()->build();
        $this->session = $cluster->connect();

        try {
            $this->session->execute(new \Cassandra\SimpleStatement('DROP KEYSPACE doctrine'));
        } catch (\Cassandra\Exception\RuntimeException $e) {
            // Cannot drop non existing keyspace 'doctrine'.
        }

        $cql       = "CREATE KEYSPACE doctrine WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 1 };";
        $statement = new \Cassandra\SimpleStatement($cql);
        $this->session->execute($statement);

        $this->session->execute(new \Cassandra\SimpleStatement('USE doctrine'));

        $cql = 'CREATE TABLE books (id int, author text, title text, PRIMARY KEY (id));';

        $this->session->execute(new \Cassandra\SimpleStatement($cql));
        $this->storage = new CassandraStorage($this->session);
    }

    public function testInsert()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('books', ['id' => 1], $data);

        $cql    = 'SELECT * FROM books WHERE id = 1';
        $result = $this->session->execute(new \Cassandra\SimpleStatement($cql));
        $rows   = iterator_to_array($result);

        $this->assertEquals('1', $rows[0]['id']);
        $this->assertEquals('John Doe', $rows[0]['author']);
        $this->assertEquals('example book', $rows[0]['title']);
    }

    public function testFind()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('books', ['id' => 2], $data);

        $this->assertEquals($data, $this->storage->find('books', ['id' => 2]));
    }

    public function testUpdate()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('books', ['id' => 3], $data);
        $this->storage->update('books', ['id' => 3], ['author' => 'Jane Doe']);

        $this->assertEquals(
            ['author' => 'Jane Doe', 'title' => 'example book'],
            $this->storage->find('books', ['id' => 3])
        );
    }

    public function testDelete()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->insert('books', ['id' => 4], $data);
        $this->storage->delete('books', ['id' => 4]);

        $this->setExpectedException('Doctrine\KeyValueStore\NotFoundException');

        $this->storage->find('books', ['id' => 4]);
    }
}
