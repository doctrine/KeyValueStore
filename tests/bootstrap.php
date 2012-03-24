<?php

if (!@include __DIR__ . '/../vendor/.composer/autoload.php') {
    die(<<<'EOT'
You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install
EOT
    );
}

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../lib/Doctrine/KeyValueStore/Mapping/Annotations/Entity.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../lib/Doctrine/KeyValueStore/Mapping/Annotations/Id.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../lib/Doctrine/KeyValueStore/Mapping/Annotations/Transient.php');

$loader = new \Doctrine\Common\ClassLoader("Doctrine\Tests", __DIR__);
$loader->register();

