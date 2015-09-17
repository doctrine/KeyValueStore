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

namespace Doctrine\KeyValueStore\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Symfony\Component\Yaml\Yaml;

class YamlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.yml';

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
        return Yaml::parse(file_get_contents($file));
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /** @var \Doctrine\KeyValueStore\Mapping\ClassMetadata $metadata */
        try {
            $element = $this->getElement($className);
        } catch (MappingException $exception) {
            throw new \InvalidArgumentException($metadata->name . ' is not a valid key-value-store entity.');
        }

        $class = new \ReflectionClass($className);

        if (isset($element['storageName'])) {
            $metadata->storageName = $element['storageName'];
        }

        $ids = [];
        if (isset($element['id'])) {
            $ids = $element['id'];
        }

        $transients = [];
        if (isset($element['transient'])) {
            $transients = $element['transient'];
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