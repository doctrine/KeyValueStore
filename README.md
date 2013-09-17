# Doctrine Key Value Stores

The Persistence interfaces are rather overkill for many implementations in the NoSQL world that are only key-value stores with some additional features on top. Doctrine Key Value Store for the rescue. This project offers a much simpler lightweight API that is centered on a key-value API to fetch/save objects.

* Single- or multi-value primary keys
* Unstructured/schema-less values that are mapped onto objects
* Depending on the implementation embedded values/objects are supported
* No complex mapping necessary, just put @Entity on the class and all properties are automatically mapped unless @Transient is given. At least one property has to be @Id. Depends on the underlying vendor though.
* Properties dont have to exist on the class, public properties are created for missing ones.
* No support for references to other objects
* EventListener for ODM/ORM that allows to manage key-value entities and collections of them as properties (postLoad, postUpdate, postPersist, postRemove)
* Stripped down Object Manager Interface
* Data-mapper as any other Doctrine library and persistence and data-objects are seperated.
* Inheritance (Single- or Multiple-Storage)

## Implementations

Following vendors are targeted:

* Microsoft Azure Table (Implemented)
* Doctrine\Common\Cache provider (Implemented)
* RDBMS (Implemented)
* Couchbase (Implemented)
* Amazon DynamoDB
* CouchDB
* MongoDB (Implemented)
* Riak (Implemented)
* Redis

We happily accept contributions for any of the drivers.

## Example

Suppose we track e-mail campaigns based on campaign id and recipients.

```php
<?php
use Doctrine\KeyValueStore\Mapping\Annotations as KeyValue;

/**
 * @KeyValue\Entity(storageName="responses")
 */
class Response
{
    const RECIEVE = 0;
    const OPEN = 10;
    const CLICK = 20;
    const ACTION = 30;

    /** @KeyValue\Id */
    private $campaign;
    /** @KeyValue\Id */
    private $recipient;
    private $status;
    private $date;

    public function __construct($campaign, $recipient, $status)
    {
        $this->campaign = $campaign;
        $this->recipient = $recipient;
        $this->status = $status;
    }
}
```

### Create

```php
<?php
$response = new Response("1234", "kontakt@beberlei.de", Response::RECIEVE);

$entityManager->persist($response);
//.... persists as much as you can :-)

$entityManager->flush();
```

### Read

```php
<?php
$response = $entityManager->find("Response",array("campaign" => "1234","recipient" => "kontakt@beberlei.de"));
```

### Update

same as create, just reuse the same id.
    
### Delete

```php
<?php
$response = $entityManager->find("Response",array("1234","kontakt@beberlei.de"));
$entityManager->remove($response);
$entityManager->flush();
```

## Configuration

There is no factory yet that simplifies the creation process, here is the
full code necessary to instantiate a KeyValue EntityManager with a Doctrine
Cache backend:

```php
<?php
use Doctrine\KeyValueStore\EntityManager;
use Doctrine\KeyValueStore\Mapping\AnnotationDriver;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
use Doctrine\KeyValueStore\Configuration;
use Doctrine\KeyValueStore\EntityManager;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\AnnotationReader;

$cache = new ArrayCache;
$storage = new DoctrineCacheStorage($cache);

$reader = new AnnotationReader();
$metadata = new AnnotationDriver($reader);
$config = new Configuration();
$config->setMappingDriverImpl($metadata);
$config->setMetadataCache($cache);

$entityManager = new EntityManager($storage, $config);
```

If you want to use WindowsAzure Table you can use the following configuration
to instantiate the storage:

```php
use Doctrine\KeyValueStore\Storage\AzureSdkTableStorage;
use WindowsAzure\Common\ServicesBuilder;

$connectionString = ""; // Windows Azure Connection string
$builder = ServicesBuilder::getInstance();
$client = $builder->createTableService($connectionString);

$storage = new AzureSdkTableStorage($client);
```

If you want to use Doctrine DBAL as backend:

```php
$params = array();
$tableName = "storage";
$idColumnName = "id";
$dataColumnName = "serialized_data";

$conn = DriverManager::getConnection($params);
$storage = new DBALStorage($conn, $tableName, $idColumnName, $dataColumnName);
```
