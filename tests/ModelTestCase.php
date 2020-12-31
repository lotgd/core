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
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Description of ModelTestCase
 */
abstract class ModelTestCase extends TestCase
{
    /** @var PDO */
    static private $pdo = null;
    /** @var EntityManager */
    static private $em = null;
    private $connection = null;
    public $g;
    protected $tables = null;

    /**
     * Returns a connection to test models
     */
    final public function getConnection()
    {
        if ($this->connection === null) {
            $configFilePath = getenv('LOTGD_TESTS_CONFIG_PATH');
            if ($configFilePath === false || strlen($configFilePath) == 0 || is_file($configFilePath) === false) {
                throw new InvalidConfigurationException("Invalid or missing configuration file: '{$configFilePath}'.");
            }
            $config = new Configuration($configFilePath);

            if (self::$pdo === null) {
                self::$pdo = new PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());

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

            $this->connection = [self::$pdo, $config->getDatabaseName()];
        }

        return $this->connection;
    }

    protected function insertData($dataSet)
    {
        /** @var PDO $pdo */
        $pdo = $this->connection[0];

        foreach ($dataSet as $table => $rows) {
            $this->tables[] = $table;
            foreach ($rows as $row) {
                $fields = implode(",", array_keys($row));
                $placeholders = substr(str_repeat("?,", count($row)), 0, -1);
                $query = "INSERT INTO $table ($fields) VALUES ($placeholders)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array_values($row));
            }
        }
    }

    /**
     * Returns the current entity manager
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return self::$em;
    }

    public function getCwd(): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..']);
    }

    public function getDataSet(): ?array
    {
        return null;
    }

    public function useSilentHandler(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        $this->getConnection();

        // Set up database content
        if (method_exists($this, "getDataSet")) {
            $dataSet = $this->getDataSet();
            if (!empty($dataSet)) {
                $this->insertData($dataSet);
            }
        }

        parent::setUp();

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // Make an empty logger for these tests. Feel free to change this
        // to place log messages somewhere you can easily find them.
        $logger  = new Logger('test');

        if ($this->useSilentHandler()) {
            $logger->pushHandler(new NullHandler());
        } else {
            $logger->pushHandler(new StreamHandler("php://stdout"));
        }

        // Create a Game object for use in these tests.
        $this->g = (new GameBuilder())
            ->withConfiguration(new Configuration(getenv('LOTGD_TESTS_CONFIG_PATH')))
            ->withLogger($logger)
            ->withEntityManager($this->getEntityManager())
            ->withCwd($this->getCwd())
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

    protected function tearDown(): void {
        parent::tearDown();

        /** @var PDO $pdo */
        $pdo = $this->connection[0];

        foreach ($this->tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE 1");
            $stmt->execute();
        }

        // Clear out the cache so tests don't get confused.
        $this->getEntityManager()->clear();
    }

    public function assertDataWasKeptIntact(?array $restrictToTables = null): void
    {
        // Assert that databases are the same before and after.
        // TODO for module author: update list of tables below to include the
        // tables you modify during registration/unregistration.
        $dataSetBefore = $this->getDataSet();
        /** @var PDO $pdo */
        $pdo = $this->getConnection()[0];

        foreach ($dataSetBefore as $table => $rowsBefore) {
            // Ignore table if $restrictToTables is an array and the table is not on the list.
            if (is_array($restrictToTables) and empty($restrictToTables[$table])) {
                continue;
            }

            $query = $pdo->query("SELECT * FROM `$table`");
            $rowsAfter = $query->fetchAll(PDO::FETCH_ASSOC);

            // Assert equal row counts
            $this->assertCount(count($rowsBefore), $rowsAfter,
                "Database assertion: Table <$table> does not match the expected number of rows. 
                Expected was <".count($rowsBefore).">, but found was <".count($rowsAfter).">"
            );

            foreach ($rowsBefore as $key => $rowBefore) {
                foreach ($rowBefore as $field => $value) {
                    $this->assertEquals($value, $rowsAfter[$key][$field],
                        "Database assertion: In table <$table>, field <$field> does not match expected value <$value>,
                        is <{$rowsAfter[$key][$field]}> instead.",
                    );
                }
            }
        }
    }

    protected function flushAndClear()
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }
}
