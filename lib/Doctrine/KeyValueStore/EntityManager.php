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

namespace Doctrine\KeyValueStore;

use Doctrine\KeyValueStore\Storage\Storage;
use Doctrine\KeyValueStore\Mapping\ClassMetadataFactory;
use Doctrine\KeyValueStore\Query\RangeQuery;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Cache\Cache;

/**
 * EntityManager for KeyValue stored objects.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class EntityManager
{
    /**
     * @var Doctrine\KeyValueStore\UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var Doctrine\KeyValueStore\Storage\Storage
     */
    private $storageDriver;

    public function __construct(Storage $storageDriver, Cache $cache, MappingDriver $mappingDriver, array $idConverters = array())
    {
        $cmf = new ClassMetadataFactory($mappingDriver);
        $cmf->setCacheDriver($cache);

        $this->unitOfWork    = new UnitOfWork($cmf, $storageDriver, $idConverters);
        $this->storageDriver = $storageDriver;
    }

    public function find($className, $key, array $fields = null)
    {
        return $this->unitOfWork->reconsititute($className, $key);
    }

    /**
     * For key-value entities that have both a partition key and a range key of
     * the combination (partition-key, range-key) you can use this operation
     * to do queries for a partition of data.
     *
     * Some vendors don't support queries at all.
     *
     * @param string $className
     * @param string $partitionKey
     * @return \Doctrine\KeyValueStore\Query\RangeQuery
     */
    public function createRangeQuery($className, $partitionKey)
    {
        return new RangeQuery($this, $className, $partitionKey);
    }

    public function persist($object)
    {
        $this->unitOfWork->scheduleForInsert($object);
    }

    public function remove($object)
    {
        $this->unitOfWork->scheduleForDelete($object);
    }

    public function flush()
    {
        $this->unitOfWork->commit();
    }

    /**
     * @return Doctrine\KeyValueStore\Storage\Storage
     */
    public function unwrap()
    {
        return $this->storageDriver;
    }

    /**
     * @return \Doctrine\KeyValueStore\UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    public function clear()
    {
        return $this->unitOfWork->clear();
    }

    /**
     * @param string $className
     * @return \Doctrine\KeyValueStore\Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->unitOfwork->getClassMetadata($className);
    }
}

