<?php
namespace Doctrine\Tests\KeyValueStore\References;

use Doctrine\KeyValueStore\References\ReferenceMetadata;

class ReferenceMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $class = new ReferenceMetadata('Foo\Bar');

        $this->assertEquals('Foo', $class->namespace);
        $this->assertEquals('Foo\Bar', $class->name);
    }

    public function testAddReferenceOne()
    {
        $class = new ReferenceMetadata('Foo\Bar');
        $class->addReferenceOne('baz', 'Baz');

        $this->assertEquals(array(
            'type' => ReferenceMetadata::REFERENCE_ONE,
            'targetEntity' => 'Foo\Baz',
        ), $class->references['baz']);
    }

    public function testAddReferenceMany()
    {
        $class = new ReferenceMetadata('Foo\Bar');
        $class->addReferenceMany('baz', 'Baz', 'bar', 'range');

        $this->assertEquals(array(
            'type' => ReferenceMetadata::REFERENCE_MANY,
            'targetEntity' => 'Foo\Baz',
            'parentField' => 'bar',
            'rangeField' => 'range',
        ), $class->references['baz']);
    }

    public function testSerialize()
    {
        $class = new ReferenceMetadata('Foo\Bar');
        $class->addReferenceOne('baz1', 'Baz');
        $class->addReferenceMany('baz2', 'Baz', 'bar', 'range');

        $class = unserialize(serialize($class));

        $this->assertEquals('Foo', $class->namespace);
        $this->assertEquals('Foo\Bar', $class->name);

        $this->assertEquals(array(
            'type' => ReferenceMetadata::REFERENCE_ONE,
            'targetEntity' => 'Foo\Baz',
        ), $class->references['baz1']);

        $this->assertEquals(array(
            'type' => ReferenceMetadata::REFERENCE_MANY,
            'targetEntity' => 'Foo\Baz',
            'parentField' => 'bar',
            'rangeField' => 'range',
        ), $class->references['baz2']);
    }
}

