Basic Usage
===========

Suppose we track e-mail campaigns based on campaign id and recipients.

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
