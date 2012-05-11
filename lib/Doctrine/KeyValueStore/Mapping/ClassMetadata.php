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

class ClassMetadata implements BaseClassMetadata
{
    public $name;
    public $storageName;
    public $rootClassName;
    public $fields = array();
    public $identifier = array();
    public $isCompositeKey = false;
    public $transientFields = array();
    public $reflFields = array();
    public $reflClass;

    private $_prototype;

    public function __construct($className)
    {
        $this->name = $className;
    }

    public function mapIdentifier($fieldName)
    {
        $this->identifier[] = $fieldName;
        $this->isCompositeKey = count($this->identifier) > 1;
        $this->mapField(array('fieldName' => $fieldName, 'id' => true));
    }

    public function mapField($mapping)
    {
        if ( ! isset($this->transientFields[$mapping['fieldName']])) {
            $this->fields[$mapping['fieldName']] = $mapping;
        }
    }

    public function skipTransientField($fieldName)
    {
        $this->transientFields[$fieldName] = true;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->_prototype === null) {
            $this->_prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }

        return clone $this->_prototype;
    }

    public function __sleep()
    {
        return array('fields', 'isCompositeKey', 'identifier', 'name');
    }

    public function getIdentifierValues($object)
    {
        $id = array();
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
    function getName(){}

    /**
     * Gets the mapped identifier field name.
     *
     * The returned structure is an array of the identifier field names.
     *
     * @return array
     */
    function getIdentifier(){}

    /**
     * Gets the ReflectionClass instance for this mapped class.
     *
     * @return ReflectionClass
     */
    function getReflectionClass(){}

    /**
     * Checks if the given field name is a mapped identifier for this class.
     *
     * @param string $fieldName
     * @return boolean
     */
    function isIdentifier($fieldName){}

    /**
     * Checks if the given field is a mapped property for this class.
     *
     * @param string $fieldName
     * @return boolean
     */
    function hasField($fieldName){}

    /**
     * Checks if the given field is a mapped association for this class.
     *
     * @param string $fieldName
     * @return boolean
     */
    function hasAssociation($fieldName){}

    /**
     * Checks if the given field is a mapped single valued association for this class.
     *
     * @param string $fieldName
     * @return boolean
     */
    function isSingleValuedAssociation($fieldName){}

    /**
     * Checks if the given field is a mapped collection valued association for this class.
     *
     * @param string $fieldName
     * @return boolean
     */
    function isCollectionValuedAssociation($fieldName){}

    /**
     * A numerically indexed list of field names of this persistent class.
     *
     * This array includes identifier fields if present on this class.
     *
     * @return array
     */
    function getFieldNames(){}

    /**
     * Returns an array of identifier field names numerically indexed.
     *
     * @return array
     */
    function getIdentifierFieldNames(){}

    /**
     * A numerically indexed list of association names of this persistent class.
     *
     * This array includes identifier associations if present on this class.
     *
     * @return array
     */
    function getAssociationNames(){}

    /**
     * Returns a type name of this field.
     *
     * This type names can be implementation specific but should at least include the php types:
     * integer, string, boolean, float/double, datetime.
     *
     * @param string $fieldName
     * @return string
     */
    function getTypeOfField($fieldName){}

    /**
     * Returns the target class name of the given association.
     *
     * @param string $assocName
     * @return string
     */
    function getAssociationTargetClass($assocName){}

    /**
     * Checks if the association is the inverse side of a bidirectional association
     *
     * @param string $assocName
     * @return boolean
     */
    function isAssociationInverseSide($assocName){}

    /**
     * Returns the target field of the owning side of the association
     *
     * @param string $assocName
     * @return string
     */
    function getAssociationMappedByTargetField($assocName){}
}

