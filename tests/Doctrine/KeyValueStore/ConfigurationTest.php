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

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\KeyValueStore\Id\IdConverterStrategy;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Doctrine\KeyValueStore\Configuration
 */
class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Configuration;
    }

    /**
     * @covers ::getMappingDriverImpl
     * @expectedException \Doctrine\KeyValueStore\KeyValueStoreException
     */
    public function testGetMappingDriverImpl()
    {
        $this->assertInstanceOf(
            MappingDriver::class,
            $this->object->getMappingDriverImpl()
        );
    }

    /**
     * @covers ::getMappingDriverImpl
     * @covers ::setMappingDriverImpl
     * @depends testGetMappingDriverImpl
     */
    public function testSetMappingDriverImpl()
    {
        $mappingDriver = $this->getMock(MappingDriver::class);

        $setterOutput = $this->object->setMappingDriverImpl($mappingDriver);

        $this->assertInstanceOf(Configuration::class, $setterOutput);
        $this->assertInstanceOf(MappingDriver::class, $this->object->getMappingDriverImpl());
    }

    /**
     * @covers ::getMetadataCache
     */
    public function testGetMetadataCache()
    {
        $this->assertInstanceOf(
            Cache::class,
            $this->object->getMetadataCache()
        );
    }

    /**
     * @covers ::setMetadataCache
     * @depends testGetMetadataCache
     */
    public function testSetMetadataCache()
    {
        $cache = $this->getMock(Cache::class);

        $setterOutput = $this->object->setMetadataCache($cache);

        $this->assertInstanceOf(Configuration::class, $setterOutput);
        $this->assertSame($cache, $this->object->getMetadataCache());
    }

    /**
     * @covers ::getIdConverterStrategy
     */
    public function testGetIdConverterStrategy()
    {
        $this->assertInstanceOf(
            IdConverterStrategy::class,
            $this->object->getIdConverterStrategy()
        );
    }

    /**
     * @covers ::setIdConverterStrategy
     * @depends testGetIdConverterStrategy
     */
    public function testSetIdConverterStrategy()
    {
        $idConverterStrategy = $this->getMock(IdConverterStrategy::class);

        $setterOutput = $this->object->setIdConverterStrategy($idConverterStrategy);

        $this->assertInstanceOf(Configuration::class, $setterOutput);
        $this->assertSame($idConverterStrategy, $this->object->getIdConverterStrategy());
    }
}
