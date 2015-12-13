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
}
