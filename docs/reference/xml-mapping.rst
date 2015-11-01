XML Mapping
===========

To use the XML driver you must configure the directory where XML mappings are
located:

.. code-block:: php

    <?php

    use Doctrine\Common\Cache\ArrayCache;
    use Doctrine\KeyValueStore\Configuration;
    use Doctrine\KeyValueStore\EntityManager;
    use Doctrine\KeyValueStore\Mapping\XmlDriver;

    $cache = new ArrayCache;
    $metadata = new XmlDriver('/path/to/xml/mappings');
    $storage = // your preferred storage

    $config = new Configuration();
    $config->setMappingDriverImpl($metadata);
    $config->setMetadataCache($cache);

    return new EntityManager($storage, $config);

Example
-------

.. code-block:: xml

    // App.Doctrine.KeyValueStore.User.dcm.xml
    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                              http://raw.github.com/doctrine/doctrine2/master/doctrine-mapping.xsd">
        <entity name="App\Doctrine\KeyValueStore\User" storage-name="users">
            <id>id</id>
        </entity>
    </doctrine-mapping>
