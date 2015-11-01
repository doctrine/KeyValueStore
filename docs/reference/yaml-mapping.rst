YAML Mapping
============

To use the YAML driver you must configure the directory where YAML mappings are
located:

.. code-block:: php

    <?php

    use Doctrine\Common\Cache\ArrayCache;
    use Doctrine\KeyValueStore\Configuration;
    use Doctrine\KeyValueStore\EntityManager;
    use Doctrine\KeyValueStore\Mapping\YamlDriver;

    $cache = new ArrayCache;
    $metadata = new YamlDriver('/path/to/yaml/mappings');
    $storage = // your preferred storage

    $config = new Configuration();
    $config->setMappingDriverImpl($metadata);
    $config->setMetadataCache($cache);

    return new EntityManager($storage, $config);

Example
-------

.. code-block:: yaml

    # App.Doctrine.KeyValueStore.User.dcm.xml
    App\Doctrine\KeyValueStore\User:
      storageName: users
      id:
      - id
