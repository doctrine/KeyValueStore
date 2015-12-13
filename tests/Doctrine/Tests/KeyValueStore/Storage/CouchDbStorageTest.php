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
            ->setConstructorArgs([
                $client,
                'test',
            ])
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
            ->will($this->returnCallback(function (array $data, $id) use (&$storedDataset) {
                $storedDataset = [$id, null];
            }));

        $storageName = rand();
        $key         = sha1(rand());

        $this->storage->insert($storageName, $key, $data);
        $this->assertNotNull($storedDataset);

        $this->assertSame([$storageName . '-' . $key, null], $storedDataset);
    }

    public function testUpdate()
    {
        $data = range(0, 10);

        $storedDataset = null;

        $this->couchdb->method('putDocument')
            ->will($this->returnCallback(function (array $data, $id) use (&$storedDataset) {
                $storedDataset = [$id, null];
            }));

        $storageName = rand();
        $key         = sha1(rand());

        $this->storage->insert($storageName, $key, $data);
        $this->assertNotNull($storedDataset);

        $this->assertSame([$storageName . '-' . $key, null], $storedDataset);

        $data = range(0, 20);

        $this->storage->insert($storageName, $key, $data);
        $this->assertNotNull($storedDataset);

        $this->assertSame([$storageName . '-' . $key, null], $storedDataset);
    }

    public function testDelete()
    {
        $storedDataset = [
            'test-foobar' => [
                'author' => 'John Doe',
                'title'  => 'example book',
            ],
        ];

        $this->couchdb->expects($this->once())
             ->method('deleteDocument')
             ->will($this->returnCallback(function ($key) use (&$storedDataset) {
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
        $storedDataset = [
            'test-foobar' => [
                'author' => 'John Doe',
                'title'  => 'example book',
            ],
        ];

        $this->couchdb->expects($this->once())
            ->method('findDocument')
            ->will($this->returnCallback(function ($key) use (&$storedDataset) {
                if (isset($storedDataset[$key])) {
                    return $storedDataset[$key];
                }

                return;
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
