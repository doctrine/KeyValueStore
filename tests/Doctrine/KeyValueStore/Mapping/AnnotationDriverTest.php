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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as CommonClassMetadata;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use stdClass;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Mapping\AnnotationDriver
 */
class AnnotationDriverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var AnnotationDriver
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->annotationReader = $this->getMock(AnnotationReader::class);
        $this->object           = new AnnotationDriver($this->annotationReader);
    }

    /**
     * @covers ::loadMetadataForClass
     */
    public function testLoadMetadataForClass()
    {
        $storageName = sha1(rand());

        $reflectionClass = $this
            ->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classAnnotation              = $this->getMock(stdClass::class);
        $classAnnotation->storageName = $storageName;

        $metadata = $this->getMock(CommonClassMetadata::class);
        $metadata
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        $this->annotationReader
            ->method('getClassAnnotation')
            ->willReturn($classAnnotation);

        $reflectionClass
            ->method('getProperties')
            ->willReturn([]);

        $this->object->loadMetadataForClass(sha1(rand()), $metadata);
    }

    /**
     * @covers ::loadMetadataForClass
     * @expectedException InvalidArgumentException
     */
    public function testWrongLoadMetadataForClass()
    {
        $metadata       = $this->getMock(CommonClassMetadata::class);
        $metadata->name = 'stdClass';

        $this->object->loadMetadataForClass(sha1(rand()), $metadata);
    }

    /**
     * @covers ::getAllClassNames
     */
    public function testGetAllClassNames()
    {
        $allClassNames = $this->object->getAllClassNames();

        $this->assertInternalType('array', $allClassNames);
    }

    /**
     * @covers ::isTransient
     */
    public function testIsTransient()
    {
        $transient = $this->object->isTransient('stdClass');

        $this->assertFalse($transient);
    }
}
