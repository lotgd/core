<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

use LotGD\Core\ComposerManager;
use LotGD\Core\LibraryConfigurationManager;
use LotGD\Core\Exceptions\InvalidConfigurationException;

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
            $dsn = getenv('LOTGD_TESTS_DATABASE_DSN');
            $user = getenv('LOTGD_TESTS_DATABASE_USER');
            $password = getenv('LOTGD_TESTS_DATABASE_PASSWORD');
            $database = getenv('LOTGD_TESTS_DATABASE_NAME');

            if ($dsn === false || strlen($dsn) == 0) {
                throw new InvalidConfigurationException("Invalid or missing configuration environment variable: LOTGD_TESTS_DATABASE_DSN.");
            }
            if ($user === false || strlen($user) == 0) {
                throw new InvalidConfigurationException("Invalid or missing configuration environment variable: LOTGD_TESTS_DATABASE_USER.");
            }
            if ($password === false) {
                throw new InvalidConfigurationException("Invalid or missing configuration environment variable: LOTGD_TESTS_DATABASE_PASSWORD.");
            }
            if ($database  === false || strlen($database) == 0) {
                throw new InvalidConfigurationException("Invalid or missing configuration environment variable: LOTGD_TESTS_DATABASE_NAME.");
            }

            if (self::$pdo === null) {
                self::$pdo = new \PDO($dsn, $user, $password);

                $composerManager = new ComposerManager(getcwd());
                $libraryConfigurationManager = new LibraryConfigurationManager($composerManager, getcwd());
                $directories = $libraryConfigurationManager->getEntityDirectories();
                $directories[] = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Models']);

                // Read db annotations from model files
                $configuration = Setup::createAnnotationMetadataConfiguration($directories, true);
                $configuration->setQuoteStrategy(new AnsiQuoteStrategy());

                self::$em = EntityManager::create(["pdo" => self::$pdo], $configuration);

                // Create Schema
                $metaData = self::$em->getMetadataFactory()->getAllMetadata();
                $schemaTool = new SchemaTool(self::$em);
                $schemaTool->updateSchema($metaData);
            }

            $this->connection = $this->createDefaultDBConnection(self::$pdo, $database);
        }

        return $this->connection;
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
