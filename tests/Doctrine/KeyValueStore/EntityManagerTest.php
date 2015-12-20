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

namespace Doctrine\KeyValueStore;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\KeyValueStore\Storage\Storage;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\EntityManager
 */
class EntityManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var EntityManager
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->storage = $this->getMock(Storage::class);
        $configuration = $this->getMock(Configuration::class);

        $configuration
            ->method('getMappingDriverImpl')
            ->willReturn($this->getMock(MappingDriver::class));

        $this->object = new EntityManager($this->storage, $configuration);
    }

    /**
     * @covers ::find
     *
     * @todo   Implement testFind().
     */
    public function testFind()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::createRangeQuery
     *
     * @todo   Implement testCreateRangeQuery().
     */
    public function testCreateRangeQuery()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::persist
     *
     * @todo   Implement testPersist().
     */
    public function testPersist()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::remove
     *
     * @todo   Implement testRemove().
     */
    public function testRemove()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::flush
     *
     * @todo   Implement testFlush().
     */
    public function testFlush()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::unwrap
     */
    public function testUnwrap()
    {
        $this->assertSame(
            $this->storage,
            $this->object->unwrap()
        );
    }

    /**
     * @covers ::getUnitOfWork
     */
    public function testGetUnitOfWork()
    {
        $this->assertInstanceOf(
            UnitOfWork::class,
            $this->object->getUnitOfWork()
        );
    }

    /**
     * @covers ::clear
     */
    public function testClear()
    {
        $this->object->clear();

        $clearedKeys = [
            'scheduledInsertions',
            'scheduledDeletions',
            'identifiers',
            'originalData',
            'identityMap',
        ];

        $unitOfWork      = $this->object->getUnitOfWork();
        $reflectionClass = new ReflectionClass($unitOfWork);

        foreach ($clearedKeys as $clearedKey) {
            $property = $reflectionClass->getProperty($clearedKey);
            $property->setAccessible(true);

            $value = $property->getValue($unitOfWork);

            $this->assertInternalType('array', $value);
            $this->assertEmpty($value);
        }
    }

    /**
     * @covers ::getClassMetadata
     *
     * @todo   Implement testGetClassMetadata().
     */
    public function testGetClassMetadata()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }
}
