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
            $this->markTestSkipped('Composite keys need to be supported for this test to run.');
        }

        $key  = ['dist' => 'foo', 'range' => 100];
        $data = [
            'dist'      => 'foo',
            'range'     => 100,
            'name'      => 'Test',
            'value'     => 1,
            'amount'    => 200.23,
            'timestamp' => new \DateTime('2012-03-26 12:12:12'),
        ];

        $this->mockInsertCompositeKey($key, $data);
        $this->storage->insert('stdClass', $key, $data);
    }

    public function testUpdateCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped('Composite keys need to be supported for this test to run.');
        }

        $key  = ['dist' => 'foo', 'range' => 100];
        $data = [
            'dist'      => 'foo',
            'range'     => 100,
            'name'      => 'Test',
            'value'     => 1,
            'amount'    => 200.23,
            'timestamp' => new \DateTime('2012-03-26 12:12:12'),
        ];

        $this->mockUpdateCompositeKey($key, $data);
        $this->storage->update('stdClass', $key, $data);
    }

    public function testDeleteCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped('Composite keys need to be supported for this test to run.');
        }

        $key = ['dist' => 'foo', 'range' => 100];

        $this->mockDeleteCompositeKey($key);
        $this->storage->delete('stdClass', $key);
    }

    public function testFindCompositeKey()
    {
        if ( ! $this->storage->supportsCompositePrimaryKeys()) {
            $this->markTestSkipped('Composite keys need to be supported for this test to run.');
        }

        $key = ['dist' => 'foo', 'range' => 100];

        $this->mockFindCompositeKey($key);
        $data = $this->storage->find('stdClass', $key);

        $this->assertEquals([
            'dist'      => 'foo',
            'range'     => '100',
            'timestamp' => new \DateTime('2008-09-18 23:46:19', new \DateTimeZone('UTC')),
            'name'      => 'Test',
            'value'     => 23,
            'amount'    => 200.23,
            'bool'      => true,
        ], $data);
    }

    abstract public function mockInsertCompositeKey($key, $data);
    abstract public function mockUpdateCompositeKey($key, $data);
    abstract public function mockDeleteCompositeKey($key);
    abstract public function mockFindCompositeKey($key);
}
