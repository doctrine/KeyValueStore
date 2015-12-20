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

namespace Doctrine\KeyValueStore\Query;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\KeyValueStore\EntityManager;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Query\RangeQuery
 */
class RangeQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $partitionKey;

    /**
     * @var RangeQuery
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->className    = sha1(rand());
        $this->partitionKey = sha1(rand());

        $this->object = new RangeQuery(
            $this->entityManager,
            $this->className,
            $this->partitionKey
        );
    }

    /**
     * @covers ::setLimit
     * @covers ::getLimit
     */
    public function testLimit()
    {
        $limit = rand();

        $setterOutput = $this->object->setLimit($limit);

        $this->assertInstanceOf(RangeQuery::class, $setterOutput);
        $this->assertSame($limit, $this->object->getLimit());
    }

    /**
     * @covers ::getClassName
     */
    public function testGetClassName()
    {
        $this->assertSame(
            $this->className,
            $this->object->getClassName()
        );
    }

    /**
     * @covers ::getPartitionKey
     */
    public function testGetPartitionKey()
    {
        $this->assertSame(
            $this->partitionKey,
            $this->object->getPartitionKey()
        );
    }

    /**
     * @covers ::getConditions
     */
    public function testGetConditions()
    {
        $reflectionClass = new ReflectionClass($this->object);
        $constants       = $reflectionClass->getConstants();

        $conditions = $this->object->getConditions();

        $this->assertInternalType('array', $conditions);

        foreach ($conditions as $condition) {
            $this->assertArrayHasKey($condition[0], $constants);
        }
    }

    /**
     * @covers ::rangeEquals
     * @depends testGetConditions
     */
    public function testRangeEquals()
    {
        $value = 'test';

        $output = $this->object->rangeEquals($value);
        $this->assertInstanceOf(RangeQuery::class, $output);

        $conditions = $this->object->getConditions();
        $this->assertArraySubset(
            [[RangeQuery::CONDITION_EQ, $value]],
            $conditions
        );
    }

    /**
     * @covers ::rangeNotEquals
     * @depends testGetConditions
     */
    public function testRangeNotEquals()
    {
        $value = 'test';

        $output = $this->object->rangeNotEquals($value);
        $this->assertInstanceOf(RangeQuery::class, $output);

        $conditions = $this->object->getConditions();
        $this->assertArraySubset(
            [[RangeQuery::CONDITION_NEQ, $value]],
            $conditions
        );
    }

    /**
     * @covers ::rangeLessThan
     * @depends testGetConditions
     */
    public function testRangeLessThan()
    {
        $value = 'test';

        $output = $this->object->rangeLessThan($value);
        $this->assertInstanceOf(RangeQuery::class, $output);

        $conditions = $this->object->getConditions();
        $this->assertArraySubset(
            [[RangeQuery::CONDITION_LT, $value]],
            $conditions
        );
    }

    /**
     * @covers ::rangeLessThanEquals
     * @depends testGetConditions
     */
    public function testRangeLessThanEquals()
    {
        $value = 'test';

        $output = $this->object->rangeLessThanEquals($value);
        $this->assertInstanceOf(RangeQuery::class, $output);

        $conditions = $this->object->getConditions();
        $this->assertArraySubset(
            [[RangeQuery::CONDITION_LE, $value]],
            $conditions
        );
    }

    /**
     * @covers ::rangeGreaterThan
     * @depends testGetConditions
     */
    public function testRangeGreaterThan()
    {
        $value = 'test';

        $output = $this->object->rangeGreaterThan($value);
        $this->assertInstanceOf(RangeQuery::class, $output);

        $conditions = $this->object->getConditions();
        $this->assertArraySubset(
            [[RangeQuery::CONDITION_GT, $value]],
            $conditions
        );
    }

    /**
     * @covers ::rangeGreaterThanEquals
     * @depends testGetConditions
     */
    public function testRangeGreaterThanEquals()
    {
        $value = 'test';

        $output = $this->object->rangeGreaterThanEquals($value);
        $this->assertInstanceOf(RangeQuery::class, $output);

        $conditions = $this->object->getConditions();
        $this->assertArraySubset(
            [[RangeQuery::CONDITION_GE, $value]],
            $conditions
        );
    }

    /**
     * @covers ::execute
     *
     * @todo   Implement testExecute().
     */
    public function testExecute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::execute
     */
    public function testWrongExecute()
    {
        $this->entityManager
            ->method('unwrap')
            ->willReturn(new DoctrineCacheStorage(new ArrayCache));

        $this->setExpectedException(RuntimeException::class);
        $this->object->execute();
    }
}
