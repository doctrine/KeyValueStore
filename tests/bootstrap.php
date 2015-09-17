<?php

if (!@include __DIR__ . '/../vendor/autoload.php') {
    die(<<<'EOT'
You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install
EOT
    );
}

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../lib/Doctrine/KeyValueStore/Mapping/Entity.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../lib/Doctrine/KeyValueStore/Mapping/Id.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../lib/Doctrine/KeyValueStore/Mapping/Transient.php');
