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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\KeyValueStore\Functional;

use Doctrine\Tests\KeyValueStoreTestCase;
use Doctrine\KeyValueStore\Mapping\Annotations as KVS;

class PersistTest extends KeyValueStoreTestCase
{
    public function testPersistUnmappedThrowsException()
    {
        $manager = $this->createManager();

        $this->setExpectedException('InvalidArgumentException', 'stdClass is not a valid key-value-store entity.');
        $manager->persist(new \stdClass());
    }

    public function testPersistWithoutIdThrowsException()
    {
        $manager = $this->createManager();
        $persist = new PersistEntity();

        $this->setExpectedException('RuntimeException', 'Trying to persist entity that has no id.');
        $manager->persist($persist);
    }

    public function testPersistKnownIdThrowsException()
    {
        $manager = $this->createManager();
        $persist = new PersistEntity();
        $persist->id = 1;

        $persist2 = new PersistEntity();
        $persist2->id = 1;

        $manager->persist($persist);

        $this->setExpectedException('RuntimeException', 'Object with ID already exists.');
        $manager->persist($persist2);
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
