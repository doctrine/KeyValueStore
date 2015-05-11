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

namespace Doctrine\KeyValueStore;

use Doctrine\KeyValueStore\Storage\Storage;
use Doctrine\KeyValueStore\Mapping\ClassMetadataFactory;
use Doctrine\KeyValueStore\Query\RangeQuery;

/**
 * EntityManager for KeyValue stored objects.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class EntityManager
{
    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var Storage
     */
    private $storageDriver;

    /**
     * Create a new EntityManager
     *
     * @param Storage $storageDriver
     * @param Configuration $config
     */
    public function __construct(Storage $storageDriver, Configuration $config)
    {
        $cmf = new ClassMetadataFactory($config->getMappingDriverImpl());
        $cmf->setCacheDriver($config->getMetadataCache());

        $this->unitOfWork    = new UnitOfWork($cmf, $storageDriver, $config);
        $this->storageDriver = $storageDriver;
    }

    /**
     * Find objects by key
     *
     * @param string $className
     * @param string|array $key
     * @return object
     */
    public function find($className, $key)
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

    /**
     * Persist new object in key value storage.
     *
     * @param object $object
     * @return void
     */
    public function persist($object)
    {
        $this->unitOfWork->scheduleForInsert($object);
    }

    /**
     * Remove object
     *
     * @param object $object
     * @return void
     */
    public function remove($object)
    {
        $this->unitOfWork->scheduleForDelete($object);
    }

    /**
     * Flush all outstanding changes from the managed object-graph into the
     * key-value storage.
     *
     * @return void
     */
    public function flush()
    {
        $this->unitOfWork->commit();
    }

    /**
     * @return Storage
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
     * @return Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->unitOfWork->getClassMetadata($className);
    }
}

