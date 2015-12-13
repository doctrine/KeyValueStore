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

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;

class SingleBasicCrudTest extends BasicCrudTestCase
{
    private $cache;

    protected function createStorage()
    {
        $this->cache = new ArrayCache();
        $storage     = new DoctrineCacheStorage($this->cache, false);
        return $storage;
    }

    public function assertKeyExists($id)
    {
        $this->assertTrue($this->cache->contains('post-' . $id));
    }

    public function assertKeyNotExists($id)
    {
        $this->assertFalse($this->cache->contains('post-' . $id));
    }

    public function populate($id, array $data)
    {
        $this->cache->save('post-' . $id, $data);
    }

    public function find($id)
    {
        return $this->storage->find('post', $id);
    }
}
