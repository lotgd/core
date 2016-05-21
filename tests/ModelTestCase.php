<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Description of ModelTestCase
 */
abstract class ModelTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var \PDO */
    static private $pdo = null;
    /** @var EntityManager */
    static private $em = null;
    /** @var \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection */
    private $connection = null;

    /**
     * Returns a connection to test models
     * @return \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
     */
    final public function getConnection(): \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
    {
        if ($this->connection === null) {
            if (self::$pdo === null) {
                self::$pdo = new \PDO(getenv('DB_DSN'), getenv('DB_USER'), getenv('DB_PASSWORD'));

                // Read db annotations from model files
                $configuration = Setup::createAnnotationMetadataConfiguration(["src/Models"], true);
                $configuration->setQuoteStrategy(new AnsiQuoteStrategy());

                $configuration->addFilter("soft-deleteable", 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');

                self::$em = EntityManager::create(["pdo" => self::$pdo], $configuration);
                self::$em->getFilters()->enable("soft-deleteable");
                self::$em->getEventManager()->addEventSubscriber(new \Gedmo\SoftDeleteable\SoftDeleteableListener());

                // Create Schema
                $metaData = self::$em->getMetadataFactory()->getAllMetadata();
                $schemaTool = new SchemaTool(self::$em);
                $schemaTool->updateSchema($metaData);
            }

            $this->connection = $this->createDefaultDBConnection(self::$pdo, getenv('DB_NAME'));
        }

        // It is important to clear the cache of the entity manager every time a new test runs!
        self::$em->clear();

        return $this->connection;
    }

    /**
     * Returns a .yml dataset under this name
     * @return \PHPUnit_Extensions_Database_DataSet_YamlDataSet
     */
    protected function getDataSet(): \PHPUnit_Extensions_Database_DataSet_YamlDataSet
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            __DIR__."/datasets/".$this->dataset.".yml"
        );
    }

    /**
     * Returns the current entity manager
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return self::$em;
    }

    protected function tearDown() {
        parent::tearDown();

        // Clear out the cache so tests don't get confused.
        $this->getEntityManager()->clear();
    }
}
