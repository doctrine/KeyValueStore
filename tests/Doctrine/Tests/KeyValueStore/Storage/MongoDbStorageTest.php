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

use Doctrine\KeyValueStore\Storage\MongoDbStorage;

/**
 * MongoDb storage testcase
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 *
 * @group legacy
 * @requires extension mongo
 */
class MongoDbStorageTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->mongo = $this->getMock('\Mongo');

        $this->mongodb = $this->getMockBuilder('\MongoDB')->disableOriginalConstructor()->getMock();

        $this->mongo->expects($this->any())
                    ->method('selectDB')
                    ->will($this->returnValue($this->mongodb));

        $this->collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();

        $this->mongodb->expects($this->once())
             ->method('selectCollection')
             ->will($this->returnValue($this->collection));

        $this->storage = new MongoDbStorage($this->mongo, [
            'collection' => 'test',
            'database'   => 'test',
        ]);
    }

    public function testInsert()
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $dbDataset = [];

        $this->collection->expects($this->once())
            ->method('insert')
            ->will($this->returnCallback(function ($data) use (&$dbDataset) {
                $dbDataset[] = $data;
            }));

        $this->storage->insert('mongodb', '1', $data);
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

        $this->collection->expects($this->once())
            ->method('update')
            ->will($this->returnCallback(function ($citeria, $data) use (&$dbDataset) {
                $dbDataset = [$citeria, $data];
            }));

        $this->storage->update('mongodb', '1', $data);

        $this->assertEquals(['key' => '1'], $dbDataset[0]);
        $this->assertEquals(['key' => '1', 'value' => $data], $dbDataset[1]);
    }

    public function testDelete()
    {
        $dataset = [
            [
                'key'   => 'foobar',
                'value' => [
                    'author' => 'John Doe',
                    'title'  => 'example book',
                ],
            ],
        ];

        $this->collection->expects($this->once())
             ->method('remove')
             ->will($this->returnCallback(function ($citeria) use (&$dataset) {
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
        $dataset = [
            [
                'key'   => 'foobar',
                'value' => [
                    'author' => 'John Doe',
                    'title'  => 'example book',
                ],
            ],
        ];

        $this->collection->expects($this->once())
            ->method('findOne')
            ->will($this->returnCallback(function ($citeria, $fields) use (&$dataset) {
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
