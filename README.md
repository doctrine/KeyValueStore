# Doctrine Key Value Stores

    This is a work in progress design document for this component

The Persistence interfaces are rather overkill for many implementations in the NoSQL world that are only key-value stores with some additional features on top. Doctrine Key Value Store for the rescue. This project offers a much simpler lightweight API that is centered on a key-value API to fetch/save objects.

* Single- or multi-value primary keys
* Unstructured/schema-less values that are mapped onto objects
* Depending on the implementation embedded values/objects are supported
* No complex mapping necessary, just put @Entity on the class and all properties are automatically mapped unless @Transient is given. At least one property has to be @Id. Depends on the underlying vendor though. Maybe we enforce two ids to enforce interoperability.
* Properties dont have to exist on the class, public properties are created for missing ones.
* No support for references to other objects
* EventListener for ODM/ORM that allows to manage key-value entities and collections of them as properties (postLoad, postUpdate, postPersist, postRemove)
* Stripped down Object Manager Interface

        <?php

        namespace Doctrine\KeyValueStore;

        class EntityManager
        {
            public function find($key);
            public function persist($object);
            public function remove($object);
            public function flush($object = null);

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
* RDBMS (id => serialized blob)

We happly accept contributions for any of the drivers.

## Example

Suppose we track e-mail campaigns based on campaign id and recipients.

    use Doctrine\KeyValueStore\Mapping\Annotations as KeyValue;

    /**
     * @KeyValue\Entity
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

    $response = new Response("1234", "kontakt@beberlei.de", Response::RECIEVE);

    $entityManager->persist($response);
    //.... persists as much as you can :-)

    $entityManager->flush();

