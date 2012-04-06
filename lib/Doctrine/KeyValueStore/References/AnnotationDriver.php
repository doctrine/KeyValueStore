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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;

/**
 * AnnotationDriver for References
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class AnnotationDriver implements Driver
{
    private $reader;

    public function __construct($reader)
    {
        $this->reader = $reader;
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     */
    function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $class = $metadata->getReflectionClass();
        if (!$class) {
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($metadata->name);
        }

        // Evaluate annotations on properties/fields
        foreach ($class->getProperties() as $property) {
            $referenceOneAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\KeyValueStore\References\Annotations\ReferenceOne');
            if ($referenceOneAnnot) {
                $metadata->addReferenceOne(
                    $property->getName(),
                    $referenceOneAnnot->targetEntity
                );
            }

            $referenceManyAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\KeyValueStore\References\Annotations\ReferenceMany');
            if ($referenceManyAnnot) {
                $metadata->addReferenceMany(
                    $property->getName(),
                    $referenceManyAnnot->targetEntity,
                    $referenceManyAnnot->parentField,
                    $referenceManyAnnot->rangeField,
                );
            }
        }
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    function getAllClassNames()
    {
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    function isTransient($className)
    {
        return false;
    }
}

