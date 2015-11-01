Annotations Reference
=====================

Index
-----

-  :ref:`@Entity <annref_entity>`
-  :ref:`@Id <annref_id>`
-  :ref:`@Transient <annref_transient>`

Reference
---------

.. _annref_entity:

@Entity
~~~~~~~

Required annotation to mark a PHP class as an entity. Doctrine manages
the persistence of all classes marked as entities.

Optional attributes:


-  **storageName**: Specifies the storage name used to prevent conflicts.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity(storageName="admin")
     */
    class User
    {
        //...
    }

.. _annref_id:

@Id
~~~~~~~

This annotation marks the identifier used to store the entity.
More properties can be marked with this annotation, but at least one
property has to be @Id.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     */
    class User
    {
    	/**
    	 * @Id
    	 */
        private $username;
    }

.. _annref_transient:

@Transient
~~~~~~~

This annotation is used to prevent the property to be stored.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     */
    class User
    {
    	/**
    	 * @Transient
    	 */
        private $temporaryData;
    }
