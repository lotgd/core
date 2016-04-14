<?php

require __DIR__ . "/bootstrap/bootstrap.php";

use Doctrine\ORM\{
    EntityManager, 
    Mapping\AnsiQuoteStrategy, 
    Tools\Setup
};

$configuration = Setup::createAnnotationMetadataConfiguration(["src/Models"], true);
$configuration->setQuoteStrategy(new \Doctrine\ORM\Mapping\AnsiQuoteStrategy());

$entityManager = EntityManager::create(["url" => "sqlite://:memory:"], $configuration);

return Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);