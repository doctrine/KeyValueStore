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

use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\Storage\RiakStorage;
use PHPUnit_Framework_TestCase;
use Riak\Client\Command\Kv\Builder\ListKeysBuilder;
use Riak\Client\Command\Kv\FetchValue;
use Riak\Client\Core\Query\RiakLocation;
use Riak\Client\Core\Query\RiakNamespace;
use Riak\Client\Core\Query\RiakObject;
use Riak\Client\Core\Transport\RiakTransportException;
use Riak\Client\RiakClient;
use Riak\Client\RiakClientBuilder;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class RiakStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RiakClient
     */
    private $client;

    /**
     * @var RiakStorage
     */
    private $storage;

    protected function setUp()
    {
        $dns = getenv('RIAK_DNS');

        if (empty($dns)) {
            $this->markTestSkipped('Missing Riak DNS');
        }

        $this->client = (new RiakClientBuilder())
            ->withNodeUri($dns)
            ->build();

        $this->storage = new RiakStorage($this->client);
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
            'title' => 'Riak test',
        ];

        $this->storage->insert('riak-test', 'foobar', $data);

        $location = $this->getRiakLocation();

        $fetch = FetchValue::builder($location)->build();

        $json = (string) $this->client
            ->execute($fetch)
            ->getValue()
            ->getValue();

        $this->assertSame($data, json_decode($json, true));
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        $data = [
            'title' => 'Riak update',
        ];

        $this->storage->insert('riak-test', 'foobar', [
            'title' => 'Riak insert',
        ]);

        $location = $this->getRiakLocation();

        $this->assertTotalBucketKeys(1, $location);

        $this->storage->update('riak-test', 'foobar', $data);

        $fetch = FetchValue::builder($location)->build();

        $json = (string) $this->client
            ->execute($fetch)
            ->getValue()
            ->getValue();

        $this->assertSame($data, json_decode($json, true));
        $this->assertTotalBucketKeys(1, $location);
    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        $this->testInsert();

        $this->storage->delete('riak-test', 'foobar');

        $location = $this->getRiakLocation();

        $fetch = FetchValue::builder($location)->build();

        $this->setExpectedException(RiakTransportException::class);
        $this->client->execute($fetch);

        $this->assertTotalBucketKeys(0, $location);
    }

    /**
     * @depends testDelete
     */
    public function testDeleteWithNotExistKey()
    {
        $this->storage->delete('riak-test', 'foobar');
        $this->storage->delete('riak-test', 'foobar');
    }

    /**
     * @depends testInsert
     */
    public function testFind()
    {
        $data = [
            'title' => 'Riak test',
        ];

        $this->storage->insert('riak-test', 'foobar', $data);

        $result = $this->storage->find('riak-test', 'foobar');

        $this->assertSame($data, $result);
    }

    public function testFindWithNotExistKey()
    {
        $this->setExpectedException(NotFoundException::class);
        $this->storage->find('riak-test', 'foobar-1');
    }

    public function testGetName()
    {
        $this->assertEquals('riak', $this->storage->getName());
    }

    private function assertTotalBucketKeys($expectedTotal, $location)
    {
        $command = (new ListKeysBuilder($location->getNamespace()))->build();

        $iterator = $this->client
            ->execute($command)
            ->getIterator();

        $this->assertCount($expectedTotal, iterator_to_array($iterator));
    }

    private function getRiakLocation()
    {
        $namespace = new RiakNamespace('default', 'riak-test');

        return new RiakLocation($namespace, 'foobar');
    }
}
