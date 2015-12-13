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

use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\Query\RangeQuery;
use Doctrine\KeyValueStore\Query\RangeQueryStorage;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Table\Models\EdmType;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\TableRestProxy;

/**
 * Storage implementation for Microsoft Windows Azure Table using the PHP SDK.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class AzureSdkTableStorage implements Storage, RangeQueryStorage
{
    /**
     * @var \WindowsAzure\Table\TableRestProxy
     */
    private $client;

    public function __construct(TableRestProxy $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsCompositePrimaryKeys()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function requiresCompositePrimaryKeys()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function insert($storageName, $key, array $data)
    {
        $entity = $this->createEntity($key, $data);

        try {
            $this->client->insertEntity($storageName, $entity);
        } catch (ServiceException $e) {
            if ($e->getCode() == 404) {
                $this->client->createTable($storageName);
            } else {
                throw new StorageException(
                    'Could not save entity in table, WindowsAzure SDK client reported error: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $entity = $this->createEntity($key, $data);

        try {
            $this->client->updateEntity($storageName, $entity);
        } catch (ServiceException $e) {
            throw new StorageException(
                'Could not update entity in table, WindowsAzure SDK client reported error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        list($partitonKey, $rowKey) = array_values($key);

        try {
            $this->client->deleteEntity($storageName, $partitonKey, $rowKey);
        } catch (ServiceException $e) {
            throw new StorageException(
                'Could not delete entity in table, WindowsAzure SDK client reported error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        list($partitonKey, $rowKey) = array_values($key);

        try {
            $result = $this->client->getEntity($storageName, $partitonKey, $rowKey);
        } catch (ServiceException $e) {
            if ($e->getCode() === 404) {
                throw new NotFoundException();
            } else {
                throw new StorageException(
                    'Could not find entity in table, WindowsAzure SDK client reported error: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        return $this->getProperties($result->getEntity());
    }

    private function getProperties(Entity $entity)
    {
        $properties = [];

        foreach ($entity->getProperties() as $name => $property) {
            if ($name === 'PartitionKey') {
                $name = 'dist';
            } elseif ($name === 'RowKey') {
                $name = 'range';
            }

            $properties[$name] = $property->getValue();
        }

        return $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'azure_table_sdk';
    }

    /**
     * {@inheritDoc}
     */
    public function executeRangeQuery(RangeQuery $query, $storageName, $key, \Closure $hydrateRow = null)
    {
        $filters = ['PartitionKey eq ' . $this->quoteFilterValue($query->getPartitionKey())];

        foreach ($query->getConditions() as $condition) {
            if ( ! in_array($condition[0], ['eq', 'neq', 'le', 'lt', 'ge', 'gt'])) {
                throw new \InvalidArgumentException(
                    'Windows Azure Table only supports eq, neq, le, lt, ge, gt as conditions.'
                );
            }
            $filters[] = 'RowKey ' . $condition[0] . ' ' . $this->quoteFilterValue($condition[1]);
        }

        $filter = '(' . implode(' and ', $filters) . ')';
        $result = $this->client->queryEntities($storageName, $filter);

        $rows = [];

        foreach ($result->getEntities() as $entity) {
            $row    = $this->getProperties($entity);
            $rows[] = $hydrateRow ? $hydrateRow($row) : $row;
        }

        return $rows;
    }

    private function quoteFilterValue($value)
    {
        return "'" . str_replace("'", '', $value) . "'";
    }

    /**
     * Create entity object with key and data values.
     *
     * @param array $key
     * @param array $data
     *
     * @return \WindowsAzure\Table\Model\Entity
     */
    private function createEntity(array $key, array $data)
    {
        list($partitonKey, $rowKey) = array_values($key);

        $entity = new Entity();
        $entity->setPartitionKey((string) $partitonKey);
        $entity->setRowKey((string) $rowKey);

        foreach ($data as $variable => $value) {
            $type = $this->getPropertyType($value);
            $entity->addProperty($variable, $type, $value);
        }

        return $entity;
    }

    /**
     * Infer the property type of variables.
     */
    private function getPropertyType($propertyValue)
    {
        if ($propertyValue instanceof \DateTime) {
            return EdmType::DATETIME;
        } elseif (is_float($propertyValue)) {
            return EdmType::DOUBLE;
        } elseif (is_int($propertyValue)) {
            return EdmType::INT32;
        } elseif (is_bool($propertyValue)) {
            return EdmType::BOOLEAN;
        }

        return EdmType::STRING;
    }
}
