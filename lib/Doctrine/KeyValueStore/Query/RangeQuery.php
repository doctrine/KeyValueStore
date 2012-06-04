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

namespace Doctrine\KeyValueStore\Query;

use Doctrine\KeyValueStore\EntityManager;

/**
 * Range Query Object. It always requires a partition/hash key and
 * optionally conditions on the range key.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class RangeQuery
{
    const CONDITION_EQ          = 'eq';
    const CONDITION_LE          = 'le';
    const CONDITION_LT          = 'lt';
    const CONDITION_GT          = 'gt';
    const CONDITION_GE          = 'ge';
    const CONDITION_NEQ         = 'neq';
    const CONDITION_BETWEEN     = 'between';
    const CONDITION_STARTSWITH  = 'startswith';

    /**
     * @param string
     */
    protected $className;

    /**
     * @param string
     */
    protected $partitionKey;

    /**
     * @var array
     */
    protected $conditions = array();

    /**
     * Limit result to only a set of entities.
     *
     * @var int|null
     */
    protected $limit;

    /**
     * Vendor specific query hints
     *
     * @var array
     */
    protected $hints = array();

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em, $className, $partitionKey)
    {
        $this->em = $em;
        $this->className = $className;
        $this->partitionKey = $partitionKey;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get className.
     *
     * @return className.
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Get partitionKey.
     *
     * @return partitionKey.
     */
    public function getPartitionKey()
    {
        return $this->partitionKey;
    }

    /**
     * Add range equals condition to range key.
     *
     * @param mixed $value
     * @return RangeQuery
     */
    public function rangeEquals($value)
    {
        $this->conditions[] = array(self::CONDITION_EQ, $value);
        return $this;
    }

    /**
     * Add range not equals condition to range key.
     *
     * @param mixed $value
     * @return RangeQuery
     */
    public function rangeNotEquals($value)
    {
        $this->conditions[] = array(self::CONDITION_NEQ, $value);
        return $this;
    }

    /**
     * Add range less than condition to range key.
     *
     * @param mixed $value
     * @return RangeQuery
     */
    public function rangeLessThan($value)
    {
        $this->conditions[] = array(self::CONDITION_LT, $value);
        return $this;
    }

    /**
     * Add range less than equals condition to range key.
     *
     * @param mixed $value
     * @return RangeQuery
     */
    public function rangeLessThanEquals($value)
    {
        $this->conditions[] = array(self::CONDITION_LE, $value);
        return $this;
    }

    /**
     * Add range greater than condition to range key.
     *
     * @param mixed $value
     * @return RangeQuery
     */
    public function rangeGreaterThan($value)
    {
        $this->conditions[] = array(self::CONDITION_GT, $value);
        return $this;
    }

    /**
     * Add range greater than equals condition to range key.
     *
     * @param mixed $value
     * @return RangeQuery
     */
    public function rangeGreaterThanEquals($value)
    {
        $this->conditions[] = array(self::CONDITION_GE, $value);
        return $this;
    }

    /**
     * Get all conditions
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Execute query and return a result iterator.
     *
     * @return ResultIterator
     */
    public function execute()
    {
        $storage = $this->em->unwrap();

        if ( ! ($storage instanceof RangeQueryStorage)) {
            throw new \RuntimeException("The storage backend " . $this->storage->getName() . " does not support range queries.");
        }

        $uow   = $this->em->getUnitOfWork();
        $class = $this->em->getClassMetadata($this->className);

        return $storage->executeRangeQuery($this, $class->storageName, $class->identifier, function ($row) use($uow, $class) {
            $key = array();
            foreach ($class->identifier as $id) {
                $key[$id] = $row[$id];
            }

            return $uow->createEntity($class, $key, $row);
        });
    }
}

