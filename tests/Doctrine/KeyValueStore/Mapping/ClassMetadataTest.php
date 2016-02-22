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

namespace Doctrine\KeyValueStore\Mapping;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use stdClass;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Mapping\ClassMetadata
 */
class ClassMetadataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ClassMetadata
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $class = rand(0, 1) ? stdClass::class : new stdClass;

        $this->object = new ClassMetadata($class);
    }

    /**
     * @covers ::skipTransientField
     */
    public function testSkipTransientField()
    {
        $field = 'foo';

        $this->object->skipTransientField($field);

        $this->assertArrayNotHasKey($field, $this->object->fields);
        $this->assertArrayHasKey($field, $this->object->transientFields);
    }

    /**
     * @covers ::mapField
     * @depends testSkipTransientField
     */
    public function testMapField()
    {
        $field = [
            'fieldName' => 'foo',
        ];

        $this->object->mapField($field);

        $this->assertArraySubset(['foo' => $field], $this->object->fields);

        $transientField = [
            'fieldName' => 'bar',
        ];

        $this->object->skipTransientField('bar');
        $this->object->mapField($transientField);

        $this->assertArrayNotHasKey('bar', $this->object->fields);
    }

    /**
     * @covers ::mapIdentifier
     * @depends testMapField
     */
    public function testMapIdentifier()
    {
        $this->object->mapIdentifier('id');

        $this->assertFalse($this->object->isCompositeKey);
        $this->assertContains('id', $this->object->identifier);

        $this->assertArraySubset([
            'id' => ['fieldName' => 'id', 'id' => true],
        ], $this->object->fields);

        $this->object->mapIdentifier('pk');

        $this->assertTrue($this->object->isCompositeKey);
        $this->assertContains('id', $this->object->identifier);
        $this->assertContains('pk', $this->object->identifier);

        $this->assertArraySubset([
            'id' => ['fieldName' => 'id', 'id' => true],
            'pk' => ['fieldName' => 'pk', 'id' => true],
        ], $this->object->fields);
    }

    /**
     * @covers ::newInstance
     */
    public function testNewInstance()
    {
        $prototype = PHPUnit_Framework_Assert::readAttribute($this->object, 'prototype');
        $this->assertNull($prototype);

        $instance = $this->object->newInstance();

        $prototype = PHPUnit_Framework_Assert::readAttribute($this->object, 'prototype');
        $this->assertNotNull($prototype);

        $this->assertInstanceOf('stdClass', $prototype);
        $this->assertInstanceOf('stdClass', $instance);

        $this->assertNotSame($prototype, $instance);
    }

    /**
     * @covers ::__sleep
     */
    public function testSleep()
    {
        $attributes = $this->object->__sleep();

        foreach ($attributes as $attribute) {
            $this->assertClassHasAttribute($attribute, ClassMetadata::class);
        }

        $this->assertInternalType('string', serialize($this->object));
    }

    /**
     * @covers ::getIdentifierValues
     */
    public function testGetIdentifierValues()
    {
        $identifierValues = $this->object->getIdentifierValues(new stdClass);

        $this->assertInternalType('array', $identifierValues);
        $this->assertEmpty($identifierValues);

        $object     = new stdClass;
        $object->id = rand();

        $this->object->mapIdentifier('id');

        $identifierValues = $this->object->getIdentifierValues($object);

        $this->assertInternalType('array', $identifierValues);
        $this->assertNotEmpty($identifierValues);
    }

    /**
     * @covers ::getName
     */
    public function testGetName()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers ::getIdentifier
     */
    public function testGetIdentifier()
    {
        $identifier = $this->object->getIdentifier();

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
        $reflectionClass = $this->object->getReflectionClass();

        $this->assertInstanceOf(ReflectionClass::class, $reflectionClass);
        $this->assertSame('stdClass', $reflectionClass->name);
    }

    /**
     * @covers ::isIdentifier
     */
    public function testIsIdentifier()
    {
        $this->object->mapIdentifier('id');

        $this->assertTrue($this->object->isIdentifier('id'));
        $this->assertFalse($this->object->isIdentifier('test'));
    }

    /**
     * @covers ::hasField
     */
    public function testHasField()
    {
        $this->object->mapField(['fieldName' => 'foo']);

        $this->assertTrue($this->object->hasField('foo'));
        $this->assertFalse($this->object->hasField('bar'));
    }

    /**
     * @covers ::hasAssociation
     */
    public function testHasAssociation()
    {
        $this->assertFalse($this->object->hasAssociation(sha1(rand())));
    }

    /**
     * @covers ::isSingleValuedAssociation
     */
    public function testIsSingleValuedAssociation()
    {
        $this->assertFalse($this->object->isSingleValuedAssociation(sha1(rand())));
    }

    /**
     * @covers ::isCollectionValuedAssociation
     */
    public function testIsCollectionValuedAssociation()
    {
        $this->assertFalse($this->object->isCollectionValuedAssociation(sha1(rand())));
    }

    /**
     * @covers ::getFieldNames
     */
    public function testGetFieldNames()
    {
        $this->object->mapField(['fieldName' => 'foo']);

        $fieldNames = $this->object->getFieldNames();

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
        $identifierFieldNames = $this->object->getIdentifierFieldNames();

        $this->assertInternalType('array', $identifierFieldNames);

        foreach ($identifierFieldNames as $key => $value) {
            $this->assertInternalType('integer', $key);
            $this->assertInternalType('string', $value);
        }
    }

    /**
     * @covers ::getAssociationNames
     */
    public function testGetAssociationNames()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers ::getTypeOfField
     */
    public function testGetTypeOfField()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers ::getAssociationTargetClass
     */
    public function testGetAssociationTargetClass()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers ::isAssociationInverseSide
     */
    public function testIsAssociationInverseSide()
    {
        $this->assertFalse($this->object->isAssociationInverseSide(sha1(rand())));
    }

    /**
     * @covers ::getAssociationMappedByTargetField
     */
    public function testGetAssociationMappedByTargetField()
    {
        $this->markTestIncomplete();
    }
}
