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

namespace Doctrine\KeyValueStore;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\KeyValueStore\Id\IdConverterStrategy;
use Doctrine\KeyValueStore\Id\NullIdConverter;

/**
 * Configure the behavior of the EntityManager
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Configuration
{
    /**
     * @var null|MappingDriver
     */
    private $mappingDriver;

    /**
     * @var null|Cache
     */
    private $metadataCache;

    /**
     * @var null|IdConverterStrategy
     */
    private $idConverter;

    /**
     * Get mapping driver implementation used with this configuration.
     *
     * @return MappingDriver
     */
    public function getMappingDriverImpl()
    {
        if (! isset($this->mappingDriver)) {
            throw KeyValueStoreException::mappingDriverMissing();
        }

        return $this->mappingDriver;
    }

    /**
     * Set the mapping driver implementation.
     *
     * @param MappingDriver $driver
     *
     * @return Configuration
     */
    public function setMappingDriverImpl(MappingDriver $driver)
    {
        $this->mappingDriver = $driver;
        return $this;
    }

    /**
     * Set the Metadata Mapping cache used with this configuration.
     *
     * @param Cache $cache
     *
     * @return Configuration
     */
    public function setMetadataCache(Cache $cache)
    {
        $this->metadataCache = $cache;
        return $this;
    }

    /**
     * Get the metadata mapping cache used with this configuration.
     *
     * @return Cache
     */
    public function getMetadataCache()
    {
        if (! isset($this->metadataCache)) {
            $this->metadataCache = new ArrayCache();
        }

        return $this->metadataCache;
    }

    /**
     * Set the ID Converter Strategy
     *
     * @param IdConverterStrategy $strategy
     *
     * @return Configuration
     */
    public function setIdConverterStrategy(IdConverterStrategy $strategy)
    {
        $this->idConverter = $strategy;
        return $this;
    }

    /**
     * Get the Id Converter strategy
     *
     * @return IdConverterStrategy
     */
    public function getIdConverterStrategy()
    {
        if (! isset($this->idConverter)) {
            $this->idConverter = new NullIdConverter();
        }

        return $this->idConverter;
    }
}
