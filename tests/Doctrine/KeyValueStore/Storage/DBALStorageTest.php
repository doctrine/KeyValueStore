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

namespace Doctrine\KeyValueStore\Storage;

use Doctrine\DBAL\Connection;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Storage\DBALStorage
 */
class DBALStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DBALStorage
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new DBALStorage($this->getMock(Connection::class));
    }

    /**
     * @covers ::supportsPartialUpdates
     *
     * @todo   Implement testSupportsPartialUpdates().
     */
    public function testSupportsPartialUpdates()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::supportsCompositePrimaryKeys
     *
     * @todo   Implement testSupportsCompositePrimaryKeys().
     */
    public function testSupportsCompositePrimaryKeys()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::requiresCompositePrimaryKeys
     *
     * @todo   Implement testRequiresCompositePrimaryKeys().
     */
    public function testRequiresCompositePrimaryKeys()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::insert
     *
     * @todo   Implement testInsert().
     */
    public function testInsert()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::update
     *
     * @todo   Implement testUpdate().
     */
    public function testUpdate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::delete
     *
     * @todo   Implement testDelete().
     */
    public function testDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::find
     *
     * @todo   Implement testFind().
     */
    public function testFind()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete();
    }

    /**
     * @covers ::getName
     */
    public function testGetName()
    {
        $this->assertEquals('dbal', $this->object->getName());
    }
}
