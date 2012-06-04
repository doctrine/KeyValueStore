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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Doctrine\KeyValueStore\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\KeyValueStore\Persistence\Mapping\ReflectionService;
use Doctrine\KeyValueStore\Mapping\ClassMetadata as KeyValueMetadata;
use Doctrine\KeyValueStore\Persistence\Mapping\AbstractClassMetadataFactory;

/**
 * Load Metadata of an entity.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    private $mappingDriver;

    public function __construct(MappingDriver $driver)
    {
        $this->mappingDriver = $driver;
    }

    protected function initialize()
    {
    }

    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        throw new \InvalidArgumentException("aliasing is not supported.");
    }

    protected function doLoadMetadata($class, $parent, $rootEntityFound)
    {
        $this->getDriver()->loadMetadataForClass($class->name, $class);

        if ($parent) {
            $class->rootClassName = $parent->name;
            $class->storageName = $parent->storageName;
        }

        if ( ! $class->storageName) {
            $parts = explode("\\", $class->name);
            $class->storageName = end($parts);
        }

        if (!$class->identifier) {
            throw new \InvalidArgumentException("Class " . $class->name . " has no identifier.");
        }
    }

    protected function newClassMetadataInstance($className)
    {
        return new KeyValueMetadata($className);
    }

    protected function getDriver()
    {
        return $this->mappingDriver;
    }

    protected function wakeupReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $class->reflClass = $reflService->getClass($class->name);
        foreach ($class->fields as $fieldName => $mapping) {
            $class->reflFields[$fieldName] = $reflService->getAccessibleProperty($class->name, $fieldName);
        }
    }

    protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $class->reflClass = $reflService->getClass($class->name);
        if ($class->reflClass) {
            foreach ($class->reflClass->getProperties() as $property) {
                $class->mapField(array('fieldName' => $property->getName()));
            }
        }
    }
}

