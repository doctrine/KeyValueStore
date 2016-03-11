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

use Doctrine\KeyValueStore\Storage\ArrayStorage;
use ReflectionProperty;

/**
 * @author Emanuele Minotto <minottoemanuele@gmail.com>
 */
class ArrayStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayStorage
     */
    private $storage;

    protected function setup()
    {
        $this->storage = new ArrayStorage();
    }

    public function testSupportsPartialUpdates()
    {
        $this->assertFalse($this->storage->supportsPartialUpdates());
    }

    public function testSupportsCompositePrimaryKeys()
    {
        $this->assertFalse($this->storage->supportsCompositePrimaryKeys());
    }

    public function testRequiresCompositePrimaryKeys()
    {
        $this->assertFalse($this->storage->requiresCompositePrimaryKeys());
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testInsert($method)
    {
        $data = [
            'author' => 'John Doe',
            'title'  => 'example book',
        ];

        $this->storage->$method('foo', 'bar', $data);

        $reflector = new ReflectionProperty(ArrayStorage::class, 'data');
        $reflector->setAccessible(true);

        $storedValue = $reflector->getValue($this->storage);

        $this->assertEquals(
            [
                'foo' => [
                    serialize('bar') => $data,
                ],
            ],
            $storedValue
        );

        $this->storage->$method('foo', 'bar', $data);
        $this->assertCount(1, $storedValue);
        $this->assertCount(1, $storedValue['foo']);
    }

    /**
     * @return array
     */
    public function methodsProvider()
    {
        return [
            ['insert'],
            ['update'],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('array', $this->storage->getName());
    }
}
