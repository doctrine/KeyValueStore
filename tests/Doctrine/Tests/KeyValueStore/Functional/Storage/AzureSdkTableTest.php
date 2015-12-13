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

use Doctrine\KeyValueStore\Query\RangeQuery;
use Doctrine\KeyValueStore\Storage\AzureSdkTableStorage;
use Doctrine\Tests\KeyValueStoreTestCase;
use WindowsAzure\Common\ServicesBuilder;

class AzureSdkTableTest extends KeyValueStoreTestCase
{
    private $storage;

    public function setUp()
    {
        parent::setUp();

        if (empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME']) || empty($GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY'])) {
            $this->markTestSkipped('Missing Azure credentials.');
        }

        $connectionString = sprintf(
            'DefaultEndpointsProtocol=http;AccountName=%s;AccountKey=%s',
            $GLOBALS['DOCTRINE_KEYVALUE_AZURE_NAME'],
            $GLOBALS['DOCTRINE_KEYVALUE_AZURE_KEY']
        );
        $tableProxy = ServicesBuilder::getInstance()->createTableService($connectionString);

        $this->storage = new AzureSdkTableStorage($tableProxy);
    }

    public function testCrud()
    {
        $storage = $this->storage;

        $key = ['dist' => 'sdktest', 'range' => time()];
        $storage->insert('test', $key, ['foo' => 'bar']);
        $data = $storage->find('test', $key);

        $this->assertInstanceOf('DateTime', $data['Timestamp']);
        $this->assertEquals('bar', $data['foo']);
        $this->assertEquals('sdktest', $data['dist']);
        $this->assertEquals($key['range'], $data['range']);

        $storage->update('test', $key, ['foo' => 'baz', 'bar' => 'baz']);
        $data = $storage->find('test', $key);

        $this->assertEquals('baz', $data['foo']);
        $this->assertEquals('baz', $data['bar']);

        $storage->delete('test', $key);

        $this->setExpectedException("Doctrine\KeyValueStore\NotFoundException");
        $storage->find('test', $key);
    }

    public function testTypes()
    {
        $storage = $this->storage;

        $data = [
            'string' => 'foo',
            'date'   => new \DateTime('now'),
            'int'    => 1234,
            'float'  => 123.45,
            'bool'   => false,
        ];

        $key = ['dist' => 'sdktest', 'range' => time()+1];
        $storage->insert('test', $key, $data);
        $data = $storage->find('test', $key);

        $this->assertInstanceOf('DateTime', $data['date']);
        $this->assertInternalType('string', $data['string']);
        $this->assertInternalType('int', $data['int']);
        $this->assertInternalType('float', $data['float']);
        $this->assertInternalType('bool', $data['bool']);
    }

    public function testQueryRange()
    {
        $rangeQuery = new RangeQuery($this->createManager(), 'test', 'sdktest');
        $rangeQuery->rangeLessThan(time());

        $data = $this->storage->executeRangeQuery($rangeQuery, 'test', ['dist', 'range'], function ($row) {
            return $row;
        });

        $this->assertTrue(count($data) > 0);
    }
}
