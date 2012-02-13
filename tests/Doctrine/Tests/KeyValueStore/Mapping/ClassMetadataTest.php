<?php
namespace Doctrine\Tests\KeyValueStore\Mapping;

use Doctrine\KeyValueStore\Mapping\ClassMetadata;

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
        $this->metadata->mapIdentifier("id");
        $this->assertFalse($this->metadata->isCompositeKey);

        $this->metadata->mapIdentifier("id2");

        $this->assertEquals(array("id", "id2"), $this->metadata->identifier);
        $this->assertTrue($this->metadata->isCompositeKey);
    }

    public function testIdentifierfield()
    {
        $this->metadata->mapIdentifier("id");

        $this->assertEquals(array('id' => array('fieldName' => 'id', 'id' => true)), $this->metadata->fields);
    }

    public function testMapField()
    {
        $this->metadata->mapField(array('fieldName' => 'metadata'));
        $this->assertEquals(array('metadata' => array('fieldName' => 'metadata')), $this->metadata->fields);
    }

    public function testSkipTransientColumns()
    {
        $this->metadata->skipTransientField('metadata');
        $this->metadata->mapField(array('fieldName' => 'metadata'));

        $this->assertEquals(array(), $this->metadata->fields);
    }
}

