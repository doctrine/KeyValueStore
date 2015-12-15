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

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\AbstractStorage;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

/**
 * @covers \Doctrine\KeyValueStore\Storage\AbstractStorage
 */
class AbstractStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractStorage
     */
    protected $object;

    public function setUp()
    {
        $this->object = $this->getMockForAbstractClass(AbstractStorage::class);
    }

    /**
     * @dataProvider keysDataProvider
     */
    public function testFlattenKey($storageName, $key, $expected)
    {
        $reflectionClass = new ReflectionClass($this->object);
        $method          = $reflectionClass->getMethod('flattenKey');
        $method->setAccessible(true);

        $hash = $method->invokeArgs($this->object, [$storageName, $key]);

        $this->assertInternalType('string', $hash);
        $this->assertSame($expected, $hash);
    }

    /**
     * @return array
     */
    public function keysDataProvider()
    {
        return [
            // key: string
            ['foo', 'bar', 'foo-bar'],
            ['foo', 0.0, 'foo-0'],
            ['foo', 0.05, 'foo-0.05'],
            ['foo', 1, 'foo-1'],
            ['foo', 1.0, 'foo-1'],
            ['foo', 1.05, 'foo-1.05'],
            ['foo', false, 'foo-'],
            ['foo', true, 'foo-1'],
            // key: array
            ['foo', ['bar', 'test'], 'foo-oid:0=bar;1=test;'],
            ['foo', ['bar', 0.0], 'foo-oid:0=bar;1=0;'],
            ['foo', ['test' => 3, 'bar' => 5], 'foo-oid:bar=5;test=3;'],
            ['foo', ['test' => 3.1, 'bar' => 5.0], 'foo-oid:bar=5;test=3.1;'],
            ['foo', ['test' => true, 'bar' => false], 'foo-oid:bar=;test=1;'],
        ];
    }
}
