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
namespace Doctrine\KeyValueStore\Integration;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ReflectionService;

/**
 * Metadata factory for Reference Mapping Metadata
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ReferenceMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * @var ObjectManager
     */
    private $parentManager;

    /**
     * @var MappingDriver
     */
    private $mappingDriver;

    public function __construct(ObjectManager $parentManager, MappingDriver $driver)
    {
        $this->mappingDriver = $driver;
        $this->parentManager = $parentManager;
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
    }

    protected function newClassMetadataInstance($className)
    {
        return new ReferenceMetadata($className);
    }

    protected function getDriver()
    {
        return $this->mappingDriver;
    }

    protected function wakeupReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $class->reflClass = $reflService->getClass($class->name);
        foreach ($class->references as $fieldName => $mapping) {
            $class->reflFields[$fieldName] = $reflService->getAccessibleProperty($class->name, $fieldName);
        }
    }

    protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $reflClass = $reflService->getClass($class->name);
        if ($reflClass) {
            foreach ($class->references as $fieldName => $mapping) {
                $class->reflFields[$fieldName] = $reflService->getAccessibleProperty($class->name, $fieldName);
            }
        }
    }
}

