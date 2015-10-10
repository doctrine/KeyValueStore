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

use Doctrine\Common\Persistence\Mapping\ClassMetadata as CommonClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\MappingException;

class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    /**
     * {@inheritDoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding file driver elements.
     *
     * @param string $file The mapping file to load.
     *
     * @return array
     */
    protected function loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string)$entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        }

        return $result;
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param CommonClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForClass($className, CommonClassMetadata $metadata)
    {
        try {
            $xmlRoot = $this->getElement($className);
        } catch (MappingException $exception) {
            throw new \InvalidArgumentException($metadata->name . ' is not a valid key-value-store entity.');
        }

        if ($xmlRoot->getName() != 'entity') {
            throw new \InvalidArgumentException($metadata->name . ' is not a valid key-value-store entity.');
        }

        $class = new \ReflectionClass($className);

        if (isset($xmlRoot['storage-name'])) {
            $metadata->storageName = $xmlRoot['storage-name'];
        }

        $ids = [];
        if (isset($xmlRoot->id)) {
            foreach ($xmlRoot->id as $id) {
                $ids[] = (string) $id;
            }
        }

        $transients = [];
        if (isset($xmlRoot->transient)) {
            foreach ($xmlRoot->transient as $transient) {
                $transients[] = (string) $transient;
            }
        }

        foreach ($class->getProperties() as $property) {
            if (in_array($property->getName(), $ids)) {
                $metadata->mapIdentifier($property->getName());

                continue;
            }

            if (in_array($property->getName(), $transients)) {
                $metadata->skipTransientField($property->getName());

                continue;
            }

            $metadata->mapField(array(
                'fieldName' => $property->getName(),
            ));
        }
    }
}