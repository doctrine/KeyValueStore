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

use Doctrine\KeyValueStore\Storage\RedisStorage;

/**
 * @author Marcel Araujo <admin@marcelaraujo.me>
 *
 * @group legacy
 * @requires extension redis
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
        $this->redis = $this->getMockBuilder('\Redis')
            ->disableOriginalConstructor()
            ->getMock();

        $this->redis->expects($this->any())
            ->method('connect')
            ->with('127.0.0.1', '6379')
            ->will($this->returnValue(true));

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
            ->will($this->returnCallback(function ($key, $data) use (&$dbDataset) {
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
            ->will($this->returnCallback(function ($key, $data) use (&$dbDataset) {
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
