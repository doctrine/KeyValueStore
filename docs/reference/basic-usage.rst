Basic Usage
===========

Requirements
------------

This guide covers getting started with the Doctrine Key Value Store.

To use the KeyValueStore you actually need:

- PHP 5.6 or above
- Composer Package Manager (`Install Composer
  <http://getcomposer.org/doc/00-intro.md>`_)

Installation
------------

To install the KeyValueStore in your project just run the Composer command:

..

    $ composer require doctrine/key-value-store

or add it to your composer.json file with:

..

    {
        "require": {
            "doctrine/key-value-store": "^1.0"
        }
    }

Usage Examples
--------------

In the guide examples we suppose to track e-mail campaigns
based on campaign id and recipients.

.. code-block:: php

    <?php

    use Doctrine\KeyValueStore\Mapping\Annotations as KeyValue;

    /**
     * @KeyValue\Entity(storageName="responses")
     */
    class Response
    {
        const RECEIVE = 0;
        const OPEN = 10;
        const CLICK = 20;
        const ACTION = 30;

        /**
         * @KeyValue\Id
         */
        private $campaign;

        /**
         * @KeyValue\Id
         */
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

Create
------

.. code-block:: php

    <?php
    $response = new Response(
        '1234',
        'kontakt@beberlei.de',
        Response::RECEIVE
    );

    $entityManager->persist($response);
    // persists as much as you can

    $entityManager->flush();

Read
----

.. code-block:: php

    <?php
    $response = $entityManager->find(
        'Response',
        array(
            'campaign' => '1234',
            'recipient' => 'kontakt@beberlei.de',
        )
    );

Update
------

Same as create, just reuse the same id.

Delete
------

.. code-block:: php

    <?php
    $response = $entityManager->find(
        'Response',
        array(
            '1234',
            'kontakt@beberlei.de',
        )
    );
    $entityManager->remove($response);
    $entityManager->flush();
