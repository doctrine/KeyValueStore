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

class UnitOfWork
{
    private $cmf;
    private $cache;
    private $storageDriver;
    private $idHandler;
    private $identityMap = array();
    private $identifiers;
    private $originalData;
    private $scheduledInsertions = array();
    private $scheduledDeletions = array();

    public function __construct($cmf, $cache, $storageDriver)
    {
        $this->cmf = $cmf;
        $this->cache = $cache;
        $this->storageDriver = $storageDriver;
        $this->idHandler = $storageDriver->supportsCompositePrimaryKeys() ?
                            new Id\CompositeIdHandler() :
                            new Id\SingleIdHandler();
    }

    public function tryGetById($id)
    {
        $idHash = $this->idHandler->hash($id);
        if (isset($this->identityMap[$idHash])) {
            return $this->identityMap[$idHash];
        }
        return null;
    }

    public function reconsititute($className, $key)
    {
        $class = $this->cmf->getMetadataFor($className);
        $id = $this->idHandler->normalizeId($class, $key);
        $data = $this->storageDriver->find($id);

        $object = $this->tryGetById($id);
        if (!$object) {
            $object = $class->newInstance();
        }
        $oid = spl_object_hash($object);
        $this->originalData[$oid] = $data;

        if ( ! isset($data['php_class']) || ! ($object instanceof $data['php_class'])) {
            throw new \RuntimeException("Trying to reconstitute " . $data['php_class'] . " but a " . $className . " was requested.");
        }

        foreach ($data as $property => $value) {
            if (isset($class->reflFields[$property])) {
                $class->reflFields[$property]->setValue($object, $value);
            } else {
                $object->$property = $value;
            }
        }

        $idHash = $this->idHandler->hash($id);
        $this->identityMap[$idHash] = $object;
        $this->identifiers[$oid] = $id;

        return $object;
    }

    private function computeChangeSet($class, $object)
    {
        $snapshot = $this->getObjectSnapshot($class, $object);
        $changeSet = array();
        $originalData = $this->originalData[spl_object_hash($object)];
        foreach ($snapshot as $field => $value) {
            if ( ! isset($originalData[$field]) || $originalData[$field] !== $value) {
                $changeSet[$field] = $value;
            }
        }

        if ( $changeSet && ! $this->storageDriver->supportsPartialUpdates()) {
            $changeSet = array_merge($originalData, $changeSet);
        }
        return $changeSet;
    }

    private function getObjectSnapshot($class, $object)
    {
        $data = array();
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            if ( ! isset( $class->fields[$fieldName]['id'])) {
                $data[$fieldName] = $reflProperty->getValue($object);
            }
        }

        foreach (get_object_vars($object) as $property => $value) {
            $data[$property] = $value;
        }

        return $data;
    }

    public function scheduleForInsert($object)
    {
        $oid = spl_object_hash($object);
        if (isset($this->identifiers[$oid])) {
            return;
        }

        $class = $this->cmf->getMetadataFor(get_class($object));
        $id = $this->idHandler->getIdentifier($class, $object);

        if ( ! $id) {
            throw new \RuntimeException("Trying to persist entity that has no id.");
        }

        $idHash = $this->idHandler->hash($id);

        if (isset($this->identityMap[$idHash])) {
            throw new \RuntimeException("Object with ID already exists.");
        }
        $this->scheduledInsertions[$oid] = $object;
    }

    public function scheduleForDelete($object)
    {
        $oid = spl_object_hash($object);
        if (!isset($this->identifiers[$oid])) {
            throw new \RuntimeException("Object scheduled for deletion is not managed. Only managed objects can be deleted.");
        }
        $this->scheduledDeletions[$oid] = $object;
    }

    private function processIdentityMap()
    {
        foreach ($this->identityMap as $object) {
            $hash = spl_object_hash($object);
            $changeSet = $this->computeChangeSet($this->cmf->getMetadataFor(get_class($object)), $object);

            if ($changeSet) {
                $changeSet['php_class'] = get_class($object);
                $this->storageDriver->update($this->identifiers[$hash], $changeSet);

                if ($this->storageDriver->supportsPartialUpdates()) {
                    $this->originalData[$hash] = array_merge($this->originalData[$hash], $changeSet);
                } else {
                    $this->originalData[$hash] = $changeSet;
                }
            }
        }
    }

    private function processInsertions()
    {
        foreach ($this->scheduledInsertions as $object) {
            $class = $this->cmf->getMetadataFor(get_class($object));
            $id = $this->idHandler->getIdentifier($class, $object);

            if ( ! $id) {
                throw new \RuntimeException("Trying to persist entity that has no id.");
            }

            $data = $this->getObjectSnapshot($class, $object);
            $data['php_class'] = get_class($object);

            $oid = spl_object_hash($object);
            $idHash = $this->idHandler->hash($id);

            $this->storageDriver->insert($id, $data);

            $this->originalData[$oid] = $data;
            $this->identifiers[$oid] = $id;
            $this->identityMap[$idHash] = $object;
        }
    }

    private function processDeletions()
    {
        foreach ($this->scheduledDeletions as $object) {
            $oid = spl_object_hash($object);
            $id = $this->identifiers[$oid];
            $idHash = $this->idHandler->hash($id);

            $this->storageDriver->delete($id);

            unset($this->identifiers[$oid], $this->originalData[$oid], $this->identityMap[$idHash]);
        }
    }

    public function commit()
    {
        $this->processIdentityMap();
        $this->processInsertions();
        $this->processDeletions();

        $this->scheduledInsertions = array();
        $this->scheduledDeletions = array();
    }
}

