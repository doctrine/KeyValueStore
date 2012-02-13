# Doctrine Key Value Stores

    This is a work in progress design document for this component

The Persistence interfaces are rather overkill for many implementations in the NoSQL world that are only key-value stores with some additional features on top. Doctrine Key Value Store for the rescue. This project offers a much simpler lightweight API that is centered on a key-value API to fetch/save objects.

* Single- or multi-value primary keys
* Unstructured/schema-less values that are mapped onto objects
* Depending on the implementation embedded values/objects are supported
* No complex mapping necessary, just put @Entity on the class and all properties are automatically mapped unless @Transient is given. At least one property has to be @Id. Depends on the underlying vendor though. Maybe we enforce two ids to enforce interoperability.
* No support for references to other objects
* EventListener for ODM/ORM that allows to manage key-value entities and collections of them as properties (postLoad, postUpdate, postPersist, postRemove)
* Stripped down Object Manager Interface

        @@@ php
        <?php

        namespace Doctrine\KeyValueStore;

        class EntityManager
        {
            public function find($key);
            public function persist($object);
            public function remove($object);
            public function flush();

            /**
             * Unwrap the underlying connection/driver.
             *
             * Can be used to access advanced APIs of storage providers. No common
             * abstraction layer can be guaranteed here anymore.
             *
             * @return object
             */
            public function unwrap();
        }

That means for this project that its a data-mapper as any other Doctrine library and persistence and data-objects are seperated.

## Implementations

Following vendors are targeted:

* Microsoft Azure Table
* Amazon DynamoDB
* CouchDB
* MongoDB
* Couchbase
* Riak
* PHP In Memory provider
* Doctrine\Common\Cache provider

