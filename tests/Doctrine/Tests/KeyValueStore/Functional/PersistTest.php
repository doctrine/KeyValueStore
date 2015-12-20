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

namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\KeyValueStore\Mapping\Annotations as KVS;
use Doctrine\Tests\KeyValueStoreTestCase;

/**
 * @group legacy
 */
class PersistTest extends KeyValueStoreTestCase
{
    /**
     * @dataProvider mappingDrivers
     */
    public function testPersistUnmappedThrowsException($mappingDriver)
    {
        $manager = $this->createManager(null, $mappingDriver);

        $this->setExpectedException('InvalidArgumentException', 'stdClass is not a valid key-value-store entity.');
        $manager->persist(new \stdClass());
    }

    /**
     * @dataProvider mappingDrivers
     */
    public function testPersistWithoutIdThrowsException($mappingDriver)
    {
        $manager = $this->createManager(null, $mappingDriver);
        $persist = new PersistEntity();

        $this->setExpectedException('RuntimeException', 'Trying to persist entity that has no id.');
        $manager->persist($persist);
    }

    /**
     * @dataProvider mappingDrivers
     */
    public function testPersistKnownIdThrowsException($mappingDriver)
    {
        $manager     = $this->createManager(null, $mappingDriver);
        $persist     = new PersistEntity();
        $persist->id = 1;

        $persist2     = new PersistEntity();
        $persist2->id = 1;

        $manager->persist($persist);

        $this->setExpectedException('RuntimeException', 'Object with ID already exists.');
        $manager->persist($persist2);
    }

    public function mappingDrivers()
    {
        return [
            ['annotation'],
            ['yaml'],
            ['xml'],
        ];
    }
}

/**
 * @KVS\Entity
 */
class PersistEntity
{
    /** @KVS\Id */
    public $id;
}
