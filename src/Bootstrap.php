<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

class Bootstrap
{
    public static function game(): Game
    {
        $pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS["DB_USER"], $GLOBALS["DB_PASSWORD"]);

        // Read db annotations from model files
        $configuration = Setup::createAnnotationMetadataConfiguration(["src/Models"], true);
        $configuration->setQuoteStrategy(new AnsiQuoteStrategy());

        $configuration->addFilter("soft-deleteable", 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');

        $entityManager = EntityManager::create(["pdo" => $pdo], $configuration);
        $entityManager->getFilters()->enable("soft-deleteable");
        $entityManager->getEventManager()->addEventSubscriber(new \Gedmo\SoftDeleteable\SoftDeleteableListener());

        // Create Schema
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);

        $eventManager = new EventManager($entityManager);

        return new Game($entityManager, $eventManager);
    }
}
