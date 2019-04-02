<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

use LotGD\Core\Configuration;
use LotGD\Core\ComposerManager;
use LotGD\Core\Doctrine\EntityPostLoadEventListener;
use LotGD\Core\GameBuilder;
use LotGD\Core\LibraryConfigurationManager;
use LotGD\Core\Exceptions\InvalidConfigurationException;
use LotGD\Core\ModelExtender;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

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
    public $g;

    /**
     * Returns a connection to test models
     * @return \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
     */
    final public function getConnection(): \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
    {
        if ($this->connection === null) {
            $configFilePath = getenv('LOTGD_TESTS_CONFIG_PATH');
            if ($configFilePath === false || strlen($configFilePath) == 0 || is_file($configFilePath) === false) {
                throw new InvalidConfigurationException("Invalid or missing configuration file: '{$configFilePath}'.");
            }
            $config = new Configuration($configFilePath);

            if (self::$pdo === null) {
                self::$pdo = new \PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());

                // Read db annotations from model files
                $composerManager = new ComposerManager(getcwd());
                $libraryConfigurationManager = new LibraryConfigurationManager($composerManager, getcwd());
                $directories = $libraryConfigurationManager->getEntityDirectories();
                $directories[] = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Models']);
                $directories[] = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Resources', 'TestModels']);

                // Read db annotations from model files
                $configuration = Setup::createAnnotationMetadataConfiguration($directories, true);

                self::$em = EntityManager::create(["pdo" => self::$pdo], $configuration);

                // Register uuid type
                \Doctrine\DBAL\Types\Type::addType('uuid', 'Ramsey\Uuid\Doctrine\UuidType');

                // Create Schema
                $metaData = self::$em->getMetadataFactory()->getAllMetadata();
                $schemaTool = new SchemaTool(self::$em);
                $schemaTool->updateSchema($metaData);
            }

            $this->connection = $this->createDefaultDBConnection(self::$pdo, $config->getDatabaseName());
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

    protected function setUp()
    {
        parent::setUp();

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // Make an empty logger for these tests. Feel free to change this
        // to place log messages somewhere you can easily find them.
        $logger  = new Logger('test');
        $logger->pushHandler(new NullHandler());

        // Create a Game object for use in these tests.
        $this->g = (new GameBuilder())
            ->withConfiguration(new Configuration(getenv('LOTGD_TESTS_CONFIG_PATH')))
            ->withLogger($logger)
            ->withEntityManager($this->getEntityManager())
            ->withCwd(implode(DIRECTORY_SEPARATOR, [__DIR__, '..']))
            ->create();

        // Add Event listener to entity manager
        $dem = $this->getEntityManager()->getEventManager();
        $dem->addEventListener([DoctrineEvents::postLoad], new EntityPostLoadEventListener($this->g));

        // Run model extender
        AnnotationRegistry::registerLoader("class_exists");

        $modelExtender = new ModelExtender();
        $libraryConfigurationManager = new LibraryConfigurationManager($this->g->getComposerManager(), getcwd());

        foreach ($libraryConfigurationManager->getConfigurations() as $config) {
            $modelExtensions = $config->getSubKeyIfItExists(["modelExtensions"]);

            if ($modelExtensions) {
                $modelExtender->addMore($modelExtensions);
            }
        }
    }

    protected function tearDown() {
        parent::tearDown();

        // Clear out the cache so tests don't get confused.
        $this->getEntityManager()->clear();
    }

    protected function flushAndClear()
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }
}
