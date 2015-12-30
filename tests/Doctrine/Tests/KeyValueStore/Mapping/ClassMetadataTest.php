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

namespace Doctrine\Tests\KeyValueStore\Mapping;

use Doctrine\KeyValueStore\Mapping\ClassMetadata;
use ReflectionClass;

/**
 * @coversDefaultClass \Doctrine\KeyValueStore\Mapping\ClassMetadata
 */
class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    private $metadata;

    public function setUp()
    {
        $this->metadata = new ClassMetadata(__CLASS__);
    }

    public function testName()
    {
        $metadata = new ClassMetadata(__CLASS__);
        $this->assertEquals(__CLASS__, $metadata->name);
    }

    public function testIdentifier()
    {
        $this->metadata->mapIdentifier('id');
        $this->assertFalse($this->metadata->isCompositeKey);

        $this->metadata->mapIdentifier('id2');

        $this->assertEquals(['id', 'id2'], $this->metadata->identifier);
        $this->assertTrue($this->metadata->isCompositeKey);
    }

    public function testIdentifierfield()
    {
        $this->metadata->mapIdentifier('id');

        $this->assertEquals(['id' => ['fieldName' => 'id', 'id' => true]], $this->metadata->fields);
    }

    public function testMapField()
    {
        $this->metadata->mapField(['fieldName' => 'metadata']);
        $this->assertEquals(['metadata' => ['fieldName' => 'metadata']], $this->metadata->fields);
    }

    public function testSkipTransientColumns()
    {
        $this->metadata->skipTransientField('metadata');
        $this->metadata->mapField(['fieldName' => 'metadata']);

        $this->assertEquals([], $this->metadata->fields);
    }

    /**
     * @covers ::getIdentifier
     */
    public function testGetIdentifier()
    {
        $identifier = $this->metadata->getIdentifier();

        $this->assertInternalType('array', $identifier);

        foreach ($identifier as $key => $value) {
            $this->assertInternalType('integer', $key);
            $this->assertInternalType('string', $value);
        }
    }

    /**
     * @covers ::getReflectionClass
     */
    public function testGetReflectionClass()
    {
        $reflectionClass = $this->metadata->getReflectionClass();

        $this->assertInstanceOf(ReflectionClass::class, $reflectionClass);
        $this->assertSame(__CLASS__, $reflectionClass->name);
    }

    /**
     * @covers ::isIdentifier
     */
    public function testIsIdentifier()
    {
        $this->metadata->mapIdentifier('id');

        $this->assertTrue($this->metadata->isIdentifier('id'));
        $this->assertFalse($this->metadata->isIdentifier('test'));
    }

    /**
     * @covers ::hasField
     */
    public function testHasField()
    {
        $this->metadata->mapField(['fieldName' => 'foo']);

        $this->assertTrue($this->metadata->hasField('foo'));
        $this->assertFalse($this->metadata->hasField('bar'));
    }

    /**
     * @covers ::hasAssociation
     */
    public function testHasAssociation()
    {
        $this->assertFalse($this->metadata->hasAssociation(sha1(rand())));
    }

    /**
     * @covers ::isSingleValuedAssociation
     */
    public function testIsSingleValuedAssociation()
    {
        $this->assertFalse($this->metadata->isSingleValuedAssociation(sha1(rand())));
    }

    /**
     * @covers ::isCollectionValuedAssociation
     */
    public function testIsCollectionValuedAssociation()
    {
        $this->assertFalse($this->metadata->isCollectionValuedAssociation(sha1(rand())));
    }

    /**
     * @covers ::getFieldNames
     */
    public function testGetFieldNames()
    {
        $this->metadata->mapField(['fieldName' => 'foo']);

        $fieldNames = $this->metadata->getFieldNames();

        $this->assertInternalType('array', $fieldNames);

        foreach ($fieldNames as $key => $value) {
            $this->assertInternalType('integer', $key);
            $this->assertInternalType('string', $value);
        }

        $this->assertSame(['foo'], $fieldNames);
    }

    /**
     * @covers ::getIdentifierFieldNames
     */
    public function testGetIdentifierFieldNames()
    {
        $identifierFieldNames = $this->metadata->getIdentifierFieldNames();

        $this->assertInternalType('array', $identifierFieldNames);

        foreach ($identifierFieldNames as $key => $value) {
            $this->assertInternalType('integer', $key);
            $this->assertInternalType('string', $value);
        }
    }

    /**
     * @covers ::isAssociationInverseSide
     */
    public function testIsAssociationInverseSide()
    {
        $this->assertFalse($this->metadata->isAssociationInverseSide(sha1(rand())));
    }
}
