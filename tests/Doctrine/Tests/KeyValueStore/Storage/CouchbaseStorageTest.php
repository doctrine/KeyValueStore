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

use Doctrine\KeyValueStore\Storage\CouchbaseStorage;

/**
 * Couchbase storage testcase
 *
 * @author Simon Schick <simonsimcity@gmail.com>
 *
 * @group legacy
 * @requires extension couchbase
 */
class CouchbaseStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $couchbase;

    /**
     * @var CouchbaseStorage
     */
    private $storage;

    protected function setUp()
    {
        $this->couchbase = $this->getMockBuilder('\Couchbase')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new CouchbaseStorage($this->couchbase);
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

        $this->couchbase->expects($this->once())
            ->method('add')
            ->will($this->returnCallback(function ($key, $data) use (&$dbDataset) {
                $dbDataset[] = ['key' => $key, 'value' => $data];
            }));

        $this->storage->insert('', '1', $data);
        $this->assertCount(1, $dbDataset);

        $this->assertEquals([['key' => '1', 'value' => $data]], $dbDataset);
    }

    public function testUpdate()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $dbDataset = [];

        $this->couchbase->expects($this->once())
            ->method('replace')
            ->will($this->returnCallback(function ($key, $data) use (&$dbDataset) {
                $dbDataset[$key] = $data;
            }));

        $this->storage->update('', '1', $data);

        $this->assertEquals(['1' => $data], $dbDataset);
    }

    public function testDelete()
    {
        $dataset = [
            'foobar' => [
                'author' => 'John Doe',
                'title'  => 'example book',
            ],
        ];

        $this->couchbase->expects($this->once())
             ->method('delete')
             ->will($this->returnCallback(function ($key) use (&$dataset) {
                    foreach ($dataset as $id => $row) {
                        if ($id === $key) {
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
        $dataset = [
            'foobar' => [
                'author' => 'John Doe',
                'title'  => 'example book',
            ],
        ];

        $this->couchbase->expects($this->once())
            ->method('get')
            ->will($this->returnCallback(function ($key) use (&$dataset) {
                if (isset($dataset[$key])) {
                    return $dataset[$key];
                }
                return;
            }
        ));

        $data = $this->storage->find('test', 'foobar');

        $this->assertEquals($dataset['foobar'], $data);
    }

    public function testGetName()
    {
        $this->assertEquals('couchbase', $this->storage->getName());
    }
}
