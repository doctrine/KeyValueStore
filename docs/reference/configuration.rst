Configuration
=============

The configuration of the KeyValueStore consists of two steps.

* Mapping Configuration
* Storage Configuration

Mapping Configuration
---------------------

.. code-block:: php

    <?php

    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\Common\Cache\ArrayCache;
    use Doctrine\KeyValueStore\Configuration;
    use Doctrine\KeyValueStore\Mapping\AnnotationDriver;

    // 1. create Configuration instance
    $config = new Configuration();

    // 2. Caching for Metadata
    $cache = new ArrayCache();
    $config->setMetadataCache($cache);

    // 3. Annotation Metadata Driver
    $reader = new AnnotationReader();
    $metadata = new AnnotationDriver($reader);
    $config->setMappingDriverImpl($metadata);

The mapping configuration is handled through a configuration object.

1. In the first step, a configuration object is created.
2. We need a caching mechanism for the configuration for performance. This
   should be different in development and production. The ``ArrayCache`` is a
   request-based cache, which is useful in development. Have a look at the
   `Doctrine Common documentation
   <http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/caching.html>`_
   for production caches.
3. Finally you have to create a metadata driver, in this example the
   ``AnnotationDriver`` that allows configurating mapping with Docblock
   annotations.

Storage Configuration
---------------------

Independent from the mapping configuration, which is the same for all key-value
database backends, you have to configure the actual storage you want to use.

This configuration is obviously specific to all the different storage drivers.
So far the following drivers exist (and are documented here):

* PHP Array
* Doctrine Cache Backend
* SQL Backend with Doctrine DBAL
* Microsoft Windows Azure Table
* Couchbase
* CouchDB
* MongoDB
* Riak

Also all those storage backends obviously have different dependencies in terms
of PHP libraries or PHP PECL extensions.

PHP Array
---------

PHP array is used mainly for development and teesting purposes.

.. code-block:: php

   <?php

   use Doctrine\KeyValueStore\Storage\ArrayStorage;

   $storage = new ArrayStorage();

Doctrine Cache Backend
----------------------

The Doctrine Cache Backend uses the `Caching Framework
<https://github.com/doctrine/cache>`_ from Doctrine as a backend. Depending on
the cache driver you get a persistent or in-memory key value store with this
solution. See the `Doctrine Common documentation
<http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/caching.html>`_
for more details about the different supported drivers.

Here is an example of configurating the Cache Storage using Redis:

.. code-block:: php

   <?php

   use Doctrine\Common\Cache\RedisCache;
   use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
   use Redis;

   $conn = new Redis(/** connection **/);
   $cache = new RedisCache();
   $cache->setRedis($conn);
   $storage = new DoctrineCacheStorage($cache);

Doctrine DBAL Backend
---------------------

You can use a relational database as backend. It uses a very simple
table as storage with one primary key and a blob field that stores
the properties.

.. code-block:: php

    <?php

    use Doctrine\DBAL\DriverManager;
    use Doctrine\KeyValueStore\Storage\DBALStorage;

    $tableName = 'storage';
    $keyColumn = 'id';
    $dataColumn = 'serialized_data';

    $conn = DriverManager::getConnection(array(
        // configuration
    ));
    $storage = new DBALStorage($conn, $tableName, $keyColumn, $dataColumn);

Microsoft Windows Azure Table
-----------------------------

Microsoft offers a NoSQL solution as part of their `Windows Azure
<http://www.windowsazure.com/en-us/>`_ service. You can use that
as a storage layer through the Windows Azure PHP SDK:

.. code-block:: php

   <?php

   use Doctrine\KeyValueStore\Storage\AzureSdkTableStorage;
   use WindowsAzure\Common\ServicesBuilder;

   $connectionString = ''; // Windows Azure Connection string
   $builder = ServicesBuilder::getInstance();
   $client = $builder->createTableService($connectionString);

   $storage = new AzureSdkTableStorage($client);

Cassandra
---------

Cassandra is supported through the `PECL extension <https://pecl.php.net/package/cassandra>`_
and the `DataStax PHP driver <https://github.com/datastax/php-driver>`_:

.. code-block:: php

    <?php

    use Cassandra;
    use Cassandra\SimpleStatement;
    use Doctrine\KeyValueStore\Storage\CassandraStorage;

    $cluster = Cassandra::cluster()->build();
    $session = $cluster->connect();
    $session->execute(new SimpleStatement('USE doctrine'));

    $storage = new CassandraStorage($session);

Couchbase
---------

Until the version 1.2 also Couchbase is supported:

.. code-block:: php

    <?php

    use Doctrine\KeyValueStore\Storage\CouchbaseStorage;

    $conn = new Couchbase(/* connection parameters */);

    $storage = new CouchbaseStorage($conn);

CouchDB
-------

CouchDB storage setup based on `doctrine/couchdb-client <https://github.com/doctrine/couchdb-client>`_:

.. code-block:: php

    <?php

    use Doctrine\CouchDB\CouchDBClient;
    use Doctrine\KeyValueStore\Storage\CouchDbStorage;

    $client = CouchDBClient::create(array(
        'dbname' => 'doctrine_example',
    ));

    $storage = new CouchDbStorage($client);

DynamoDb
---------

DynamoDb is supported through the `AWS SDK for PHP <https://aws.amazon.com/sdk-for-php/>`_
Create your tables via the AWS DynamoDb console or using the `PHP based API <http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/LowLevelPHPTableOperationsExample.html>`_
See the `AWS docs <http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/UsingPHP.html#PHPSDKCredentialsSet>`_ for more information on configuring credentials for the client.

.. code-block:: php

    <?php

    $sdk = new \Aws\Sdk([...]);
    $client = $sdk->createDynamoDb();

    $storage = new DynamoDbStorage(
        $client,
        // Optional key name, defaults to Id.
        null,
        // Optional table name/ key name pairs.
        // This example uses a table called Awesome keyed by MyKey.
        ['storage_keys' => ['Awesome' => 'MyKey']]
    );

MongoDB
-------

Mongo support is provided using a `Mongo <http://php.net/manual/en/class.mongo.php>`_
instance, the collection name and the database name.

Both the options ``collection`` and ``database`` are required.

.. code-block:: php

    <?php

    use Doctrine\KeyValueStore\Storage\MongoDbStorage;

    $conn = new \Mongo(/* connection parameters and options */);

    $storage = new MongoDbStorage($conn, array(
        'collection' => 'your_collection',
        'database'   => 'your_database',
    ));

Riak
----

Riak support is provided through the library `riak/riak-client <https://github.com/nacmartin/riak-client>`_ :

.. code-block:: php

    <?php

    use Doctrine\KeyValueStore\Storage\RiakStorage;
    use Riak\Client;

    $conn = new Riak(/* connection parameters */);

    $storage = new RiakStorage($conn);
