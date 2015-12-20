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

use Doctrine\KeyValueStore\Mapping\ClassMetadataFactory;
use Doctrine\KeyValueStore\Storage\Storage;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\UnitOfWork
 */
class UnitOfWorkTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var UnitOfWork
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $classMetadataFactory = $this
            ->getMockBuilder(ClassMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage       = $this->getMock(Storage::class);
        $configuration = $this->getMock(Configuration::class);

        $this->object = new UnitOfWork($classMetadataFactory, $storage, $configuration);
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

    /**
     * @covers ::reconstititute
     *
     * @todo   Implement testReconstititute().
     */
    public function testReconstititute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::createEntity
     *
     * @todo   Implement testCreateEntity().
     */
    public function testCreateEntity()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::scheduleForInsert
     *
     * @todo   Implement testScheduleForInsert().
     */
    public function testScheduleForInsert()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::scheduleForDelete
     *
     * @todo   Implement testScheduleForDelete().
     */
    public function testScheduleForDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::commit
     *
     * @todo   Implement testCommit().
     */
    public function testCommit()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::clear
     */
    public function testClear()
    {
        $clearedKeys = [
            'scheduledInsertions',
            'scheduledDeletions',
            'identifiers',
            'originalData',
            'identityMap',
        ];

        $this->object->clear();

        $reflectionClass   = new ReflectionClass($this->object);
        $defaultProperties = $reflectionClass->getDefaultProperties();

        foreach ($clearedKeys as $clearedKey) {
            $property = $reflectionClass->getProperty($clearedKey);
            $property->setAccessible(true);

            $this->assertSame(
                $defaultProperties[$clearedKey],
                $property->getValue($this->object)
            );
        }
    }
}
