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

namespace Doctrine\KeyValueStore\References;

/**
 * Reference Metadata
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ReferenceMetadata
{
    const REFERENCE_ONE  = 1;
    const REFERENCE_MANY = 2;

    public $name;
    public $namespace;
    public $references = array();
    public $reflFields = array();

    public function __construct($name)
    {
        $this->name = $name;
        if ($pos = strrpos($name, "\\")) {
            $this->namespace = substr($name, 0, $pos);
        }
    }

    public function addReferenceOne($fieldName, $targetEntity)
    {
        if ($this->namespace || strpos($targetEntity, "\\") === false) {
            $targetEntity = $this->namespace . "\\" . $targetEntity;
        }

        $this->references[$fieldName] = array(
            'type' => self::REFERENCE_ONE,
            'targetEntity' => $targetEntity
        );
    }

    public function addReferenceMany($fieldName, $targetEntity, $parentField, $rangeField)
    {
        if ($this->namespace || strpos($targetEntity, "\\") === false) {
            $targetEntity = $this->namespace . "\\" . $targetEntity;
        }

        $this->references[$fieldName] = array(
            'type' => self::REFERENCE_MANY,
            'targetEntity' => $targetEntity,
            'parentField' => $parentField,
            'rangeField' => $rangeField,
        );
    }

    public function __sleep()
    {
        return array('name', 'namespace', 'references');
    }
}

