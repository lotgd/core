<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use LotGD\Core\Doctrine\EntityPostLoadEventListener;
use LotGD\Core\Exceptions\InvalidConfigurationException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

/**
 * The entry point for constructing a properly configured LotGD Game object.
 */
class Bootstrap
{
    private $logger;
    private $game;
    /** @var LibraryConfigurationManager */
    private $libraryConfigurationManager;
    private $annotationDirectories = [];

    /**
     * Create a new Game object, with all the necessary configuration.
     * @param string|null $cwd
     * @return Game The newly created Game object.
     */
    public static function createGame(string $cwd = null): Game
    {
        $game = new self();
        $cwd = $cwd ?? \getcwd();
        return $game->getGame($cwd);
    }

    /**
     * Starts the game kernel with the most important classes and returns the object.
     * @param string $cwd
     * @return Game
     */
    public function getGame(string $cwd): Game
    {
        $config = $this->createConfiguration($cwd);
        $this->logger = $this->createLogger($config, "lotgd");
        $v = Game::getVersion();
        $this->logger->info("Bootstrap (Daenerys 🐲{$v}).");

        $composer = $this->createComposerManager($cwd);
        $this->libraryConfigurationManager = $this->createLibraryConfigurationManager($composer, $cwd);

        [$dsn, $user, $password] = $config->getDatabaseConnectionDetails($cwd);
        $pdo = $this->connectToDatabase($dsn, $user, $password);
        $entityManager = $this->createEntityManager($pdo, $config);

        $this->game = (new GameBuilder())
            ->withConfiguration($config)
            ->withLogger($this->logger)
            ->withEntityManager($entityManager)
            ->withCwd($cwd)
            ->create()
        ;

        // Add Event listener to entity manager
        $dem = $entityManager->getEventManager();
        $dem->addEventListener([DoctrineEvents::postLoad], new EntityPostLoadEventListener($this->game));

        // Run model extender
        $this->extendModels();

        return $this->game;
    }

    /**
     * Creates a library configuration manager.
     * @param ComposerManager $composerManager
     * @param string $cwd
     * @return LibraryConfigurationManager
     */
    protected function createLibraryConfigurationManager(
        ComposerManager $composerManager,
        string $cwd
    ): LibraryConfigurationManager {
        return new LibraryConfigurationManager($composerManager, $cwd);
    }

    /**
     * Connects to a database using pdo.
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @return \PDO
     */
    protected function connectToDatabase(string $dsn, string $user, string $password): \PDO
    {
        return new \PDO($dsn, $user, $password);
    }

    /**
     * Creates and returns an instance of ComposerManager.
     * @param string $cwd
     * @return ComposerManager
     */
    protected function createComposerManager(string $cwd): ComposerManager
    {
        $composer = new ComposerManager($cwd);
        return $composer;
    }

    /**
     * Returns a configuration object reading from the file located at the path stored in $cwd/config/lotgd.yml.
     * @param string $cwd
     * @throws InvalidConfigurationException
     * @return Configuration
     */
    protected function createConfiguration(string $cwd): Configuration
    {
        if (empty($configFilePath)) {
            $configFilePath = \implode(\DIRECTORY_SEPARATOR, [$cwd, "config", "lotgd.yml"]);
        }

        if ($configFilePath === false || \strlen($configFilePath) == 0 || \is_file($configFilePath) === false) {
            throw new InvalidConfigurationException("Invalid or missing configuration file: {$configFilePath}.");
        }

        $config = new Configuration($configFilePath);
        return $config;
    }

    /**
     * Returns a logger instance.
     * @param Configuration $config
     * @param string $name
     * @return LoggerInterface
     */
    protected function createLogger(Configuration $config, string $name): LoggerInterface
    {
        $logger = new Logger($name);
        // Add lotgd as the prefix for the log filenames.
        $logger->pushHandler(new RotatingFileHandler($config->getLogPath() . \DIRECTORY_SEPARATOR . $name, 14));

        return $logger;
    }

    /**
     * Creates the EntityManager using the pdo connection given in it's argument.
     * @param \PDO $pdo
     * @param Configuration
     * @return EntityManagerInterface
     */
    protected function createEntityManager(\PDO $pdo, Configuration $config): EntityManagerInterface
    {
        $this->annotationDirectories = $this->generateAnnotationDirectories();
        $this->logger->addDebug("Adding annotation directories:");
        foreach ($this->annotationDirectories as $d) {
            $this->logger->addDebug("  {$d}");
        }
        $configuration = Setup::createAnnotationMetadataConfiguration($this->annotationDirectories, true);

        // Create entity manager
        $entityManager = EntityManager::create(["pdo" => $pdo], $configuration);

        // Register uuid type
        try {
            Type::addType('uuid', 'Ramsey\Uuid\Doctrine\UuidType');
        } catch (DBALException $e) {
        }

        // Create Schema and update database if needed
        if ($config->getDatabaseAutoSchemaUpdate()) {
            $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->updateSchema($metaData);
        }

        return $entityManager;
    }

    /**
     * Is used to get all directories used to generate annotations.
     * @return array
     */
    protected function generateAnnotationDirectories(): array
    {
        // Read db annotations from our own model files.
        $directories = [__DIR__ . \DIRECTORY_SEPARATOR . 'Models'];

        // Get additional annotation directories from library configs.
        $libraryDirectories = $this->libraryConfigurationManager->getEntityDirectories();

        return \array_merge($directories, $libraryDirectories);
    }

    /**
     * Adds Symfony/Console commands to the provided application from configured libraries.
     * @param Application $application
     */
    public function addDaenerysCommands(Application $application)
    {
        foreach ($this->libraryConfigurationManager->getConfigurations() as $config) {
            $commands = $config->getDaenerysCommands();
            foreach ($commands as $command) {
                $application->add(new $command($this->game));
            }
        }
    }

    /**
     * Runs the code to extend models.
     */
    public function extendModels()
    {
        AnnotationRegistry::registerLoader("class_exists");

        $modelExtender = new ModelExtender();

        foreach ($this->libraryConfigurationManager->getConfigurations() as $config) {
            $modelExtensions = $config->getSubKeyIfItExists(["modelExtensions"]);

            if ($modelExtensions) {
                $modelExtender->addMore($modelExtensions);
            }
        }
    }
}
