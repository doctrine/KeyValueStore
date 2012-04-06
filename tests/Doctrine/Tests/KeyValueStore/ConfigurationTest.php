<?php
namespace Doctrine\Tests\KeyValueStore;

use Doctrine\KeyValueStore\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testNoMappingDriver()
    {
        $config = new Configuration();

        $this->setExpectedException('Doctrine\KeyValueStore\KeyValueStoreException', 'No mapping driver was assigned to the configuration. Use $config->setMappingDriverImpl()');
        $config->getMappingDriverImpl();
    }

    public function testDefaultCacheDriver()
    {
        $config = new Configuration();
        $cache = $config->getMetadataCache();

        $this->assertInstanceOf('Doctrine\Common\Cache\Cache', $cache);
    }

    public function testDefaultIdConverterStrategy()
    {
        $config = new Configuration();
        $strategy = $config->getIdConverterStrategy();

        $this->assertInstanceOf('Doctrine\KeyValueStore\Id\NullIdConverter', $strategy);
    }
}

