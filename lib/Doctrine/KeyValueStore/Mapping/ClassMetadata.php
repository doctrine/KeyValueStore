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

namespace Doctrine\KeyValueStore\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use ReflectionClass;

class ClassMetadata implements BaseClassMetadata
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $storageName;

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var array
     */
    public $identifier = [];

    /**
     * @var bool
     */
    public $isCompositeKey = false;

    /**
     * @var array
     */
    public $transientFields = [];

    /**
     * @var array
     */
    public $reflFields = [];

    /**
     * @var null|mixed
     */
    private $prototype;

    /**
     * @param string|object $class
     */
    public function __construct($class)
    {
        if (is_object($class)) {
            $reflectionClass = new ReflectionClass($class);
            $class           = $reflectionClass->getName();
        }

        $this->name = $class;
    }

    /**
     * Add a mapped identifier.
     *
     * @param string $fieldName
     */
    public function mapIdentifier($fieldName)
    {
        $this->identifier[]   = $fieldName;
        $this->isCompositeKey = count($this->identifier) > 1;
        $this->mapField([
            'fieldName' => $fieldName,
            'id'        => true,
        ]);
    }

    /**
     * Add a mapped field.
     *
     * @param array $mapping
     */
    public function mapField(array $mapping)
    {
        if (! isset($this->transientFields[$mapping['fieldName']])) {
            $this->fields[$mapping['fieldName']] = $mapping;
        }
    }

    /**
     * Add a transient field.
     *
     * @param string $fieldName
     */
    public function skipTransientField($fieldName)
    {
        // it's necessary to unset because ClassMetadataFactory::initializeReflection has already run
        // and the fields have all been mapped -- even the transient ones
        unset($this->fields[$fieldName]);
        $this->transientFields[$fieldName] = true;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->prototype === null) {
            $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }

        return clone $this->prototype;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['fields', 'isCompositeKey', 'identifier', 'name', 'storageName'];
    }

    /**
     * Get identifiers values.
     *
     * @param string|object $object
     *
     * @return array
     */
    public function getIdentifierValues($object)
    {
        $id = [];
        foreach ($this->identifier as $field) {
            $value = $this->reflFields[$field]->getValue($object);
            if ($value !== null) {
                $id[$field] = $value;
            }
        }
        return $id;
    }

    /**
     * Get fully-qualified class name of this persistent class.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the mapped identifier field name.
     *
     * The returned structure is an array of the identifier field names.
     *
     * @return array
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Gets the ReflectionClass instance for this mapped class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        return new ReflectionClass($this->name);
    }

    /**
     * Checks if the given field name is a mapped identifier for this class.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isIdentifier($fieldName)
    {
        return in_array($fieldName, $this->identifier);
    }

    /**
     * Checks if the given field is a mapped property for this class.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Checks if the given field is a mapped association for this class.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasAssociation($fieldName)
    {
        return false;
    }

    /**
     * Checks if the given field is a mapped single valued association for this class.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return false;
    }

    /**
     * Checks if the given field is a mapped collection valued association for this class.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return false;
    }

    /**
     * A numerically indexed list of field names of this persistent class.
     *
     * This array includes identifier fields if present on this class.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_column($this->fields, 'fieldName');
    }

    /**
     * Returns an array of identifier field names numerically indexed.
     *
     * @return array
     */
    public function getIdentifierFieldNames()
    {
        return $this->identifier;
    }

    /**
     * A numerically indexed list of association names of this persistent class.
     *
     * This array includes identifier associations if present on this class.
     *
     * @return array
     */
    public function getAssociationNames()
    {
    }

    /**
     * Returns a type name of this field.
     *
     * This type names can be implementation specific but should at least include the php types:
     * integer, string, boolean, float/double, datetime.
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getTypeOfField($fieldName)
    {
    }

    /**
     * Returns the target class name of the given association.
     *
     * @param string $assocName
     *
     * @return string
     */
    public function getAssociationTargetClass($assocName)
    {
    }

    /**
     * Checks if the association is the inverse side of a bidirectional association
     *
     * @param string $assocName
     *
     * @return bool
     */
    public function isAssociationInverseSide($assocName)
    {
        return false;
    }

    /**
     * Returns the target field of the owning side of the association
     *
     * @param string $assocName
     *
     * @return string
     */
    public function getAssociationMappedByTargetField($assocName)
    {
    }
}
