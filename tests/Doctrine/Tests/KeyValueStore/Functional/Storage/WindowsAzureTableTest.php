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

use Doctrine\KeyValueStore\Http\SocketClient;
use Doctrine\KeyValueStore\Query\RangeQuery;
use Doctrine\KeyValueStore\Storage\WindowsAzureTable\SharedKeyLiteAuthorization;
use Doctrine\KeyValueStore\Storage\WindowsAzureTableStorage;
use Doctrine\Tests\KeyValueStoreTestCase;

class WindowsAzureTableTest extends KeyValueStoreTestCase
{
    private $storage;

    public function setUp()
    {
        parent::setUp();

        if (empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME']) || empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY'])) {
            $this->markTestSkipped('Missing Azure credentials.');
        }

        switch ($GLOBALS['DOCTRINE_KEYVALUE_AZURE_AUTHSCHEMA']) {
            case 'sharedlite':
                $auth = new SharedKeyLiteAuthorization(
                    $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
                    $GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY']
                );
                break;
        }

        $this->storage = new WindowsAzureTableStorage(
            new SocketClient(),
            $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
            $auth
        );
    }

    public function testCrud()
    {
        $storage = $this->storage;

        $key = ['dist' => 'foo', 'range' => time()];
        $storage->insert('test', $key, ['foo' => 'bar']);
        $data = $storage->find('test', $key);

        $this->assertInstanceOf('DateTime', $data['Timestamp']);
        $this->assertEquals('bar', $data['foo']);
        $this->assertEquals('foo', $data['dist']);
        $this->assertEquals($key['range'], $data['range']);

        $storage->update('test', $key, ['foo' => 'baz', 'bar' => 'baz']);
        $data = $storage->find('test', $key);

        $this->assertEquals('baz', $data['foo']);
        $this->assertEquals('baz', $data['bar']);

        $storage->delete('test', $key);

        $this->setExpectedException("Doctrine\KeyValueStore\Exception\NotFoundException");
        $storage->find('test', $key);
    }

    public function testQueryRange()
    {
        $rangeQuery = new RangeQuery($this->createManager(), 'test', 'foo');
        $rangeQuery->rangeLessThan(time());

        $data = $this->storage->executeRangeQuery($rangeQuery, 'test', ['dist', 'range'], function ($row) {
            return $row;
        });

        $this->assertTrue(count($data) > 0);
    }
}
