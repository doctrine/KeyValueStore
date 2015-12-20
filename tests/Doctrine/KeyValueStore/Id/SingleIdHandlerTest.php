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

namespace Doctrine\KeyValueStore\Id;

use Doctrine\KeyValueStore\Mapping\ClassMetadata;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Id\SingleIdHandler
 */
class SingleIdHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SingleIdHandler
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new SingleIdHandler;
    }

    /**
     * @covers ::normalizeId
     */
    public function testNormalizeId()
    {
        $metadata = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $key = rand();

        $normalizedId = $this->object->normalizeId($metadata, $key);
        $this->assertSame($key, $normalizedId);

        $metadata->identifier = ['id'];
        $key                  = [
            'id' => rand(),
        ];

        $normalizedId = $this->object->normalizeId($metadata, $key);
        $this->assertSame($key['id'], $normalizedId);
    }

    /**
     * @covers ::getIdentifier
     */
    public function testGetIdentifier()
    {
        $data = rand();

        $metadata = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $object = new stdClass;

        $metadata->identifier = ['id'];
        $metadata
            ->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->equalTo($object))
            ->willReturn([
                'id' => $data,
            ]);

        $identifier = $this->object->getIdentifier($metadata, $object);
        $this->assertSame($data, $identifier);
    }

    /**
     * @covers ::hash
     */
    public function testHash()
    {
        $key = rand();

        $this->assertSame(
            $key,
            $this->object->hash($key)
        );
    }
}
