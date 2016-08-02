<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\ {
    EntityManager,
    EntityManagerInterface,
    Mapping\AnsiQuoteStrategy,
    Tools\Setup,
    Tools\SchemaTool
};
use Monolog\ {
    Logger,
    Handler\RotatingFileHandler
};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

use LotGD\Core\ {
    ComposerManager,
    BootstrapInterface,
    Exceptions\InvalidConfigurationException
};

/**
 * The entry point for constructing a properly configured LotGD Game object.
 */
class Bootstrap
{
    private $rootDir;
    private $game;
    private $bootConfigurationManager = [];
    private $annotationDirectories = [];

    /**
     * Create a new Game object, with all the necessary configuration.
     * @param string $rootDir The root directory if it is different from getcwd()
     * @return Game The newly created Game object.
     */
    public static function createGame(string $rootDir = null): Game
    {
        $game = new self();
        return $game->getGame($rootDir);
    }

    /**
     * Starts the game kernel with the most important classes and returns the object
     * @param string $rootDir The root directory if it is different from getcwd()
     * @return Game
     */
    public function getGame(string $rootDir = null): Game
    {
        $this->rootDir = $rootDir ?? getcwd();

        $composer = $this->createComposerManager();
        $this->bootConfigurationManager = $this->createBootConfigurationManager($composer, $this->rootDir);

        $config = $this->createConfiguration();
        $logger = $this->createLogger($config, "lotgd");

        $pdo = $this->connectToDatabase($config);
        $entityManager = $this->createEntityManager($pdo);

        $this->game = new Game($config, $logger, $entityManager);

        return $this->game;
    }

    /**
     * Creates the boot configuration manager
     * @param ComposerManager $composerManager
     * @param string $cwd
     * @return \LotGD\Core\BootConfigurationManager
     */
    protected function createBootConfigurationManager(
        ComposerManager $composerManager,
        string $cwd
    ): BootConfigurationManager {
        return new BootConfigurationManager($composerManager, $cwd);
    }

    /**
     * Connects to a database using pdo
     * @param \LotGD\Core\Configuration $config
     * @return \PDO
     */
    protected function connectToDatabase(Configuration $config): \PDO
    {
        return new \PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());
    }

    /**
     * Creates and returns an instance of ComposerManager
     * @param Logger $logger
     * @return ComposerManager
     */
    protected function createComposerManager(): ComposerManager
    {
        $composer = new ComposerManager();
        return $composer;
    }

    /**
     * Returns a configuration object reading from the file located at the path stored in LOTGD_CONFIG.
     * @return \LotGD\Core\Configuration
     * @throws InvalidConfigurationException
     */
    protected function createConfiguration(): Configuration
    {
        $configFilePath = getenv('LOTGD_CONFIG');

        if (empty($configFilePath)) {
            $configFilePath = implode(DIRECTORY_SEPARATOR, [$this->rootDir, "config", "lotgd.yml"]);
        }
        else {
            $configFilePath = $this->rootDir . DIRECTORY_SEPARATOR . $configFilePath;
        }

        if ($configFilePath === false || strlen($configFilePath) == 0 || is_file($configFilePath) === false) {
            throw new InvalidConfigurationException("Invalid or missing configuration file: {$configFilePath}.");
        }

        $config = new Configuration($configFilePath, $this->rootDir);
        return $config;
    }

    /**
     * Returns a logger instance
     * @param type $name
     * @return LoggerInterface
     */
    protected function createLogger(Configuration $config, string $name): LoggerInterface
    {
        $logger = new Logger($name);
        // Add lotgd as the prefix for the log filenames.
        $logger->pushHandler(new RotatingFileHandler($config->getLogPath() . DIRECTORY_SEPARATOR . $name, 14));

        $v = Game::getVersion();
        $logger->info("Bootstrap constructing game (Daenerys 🐲{$v}).");

        return $logger;
    }

    /**
     * Creates the EntityManager using the pdo connection given in it's argument
     * @param \PDO $pdo
     * @return EntityManagerInterface
     */
    protected function createEntityManager(\PDO $pdo): EntityManagerInterface
    {
        $this->annotationDirectories = $this->generateAnnotationDirectories();
        $configuration = Setup::createAnnotationMetadataConfiguration($this->annotationDirectories, true);

        // Set a quote
        $configuration->setQuoteStrategy(new AnsiQuoteStrategy());

        // Create entity manager
        $entityManager = EntityManager::create(["pdo" => $pdo], $configuration);

        // Create Schema and update database if needed
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);

        return $entityManager;
    }

    /**
     * Is used to get all directories used to generate annotations.
     * @param array $bootstrapClasses
     * @return array
     */
    protected function generateAnnotationDirectories(): array
    {
        // Read db annotations from our own model files.
        $directories = [__DIR__ . DIRECTORY_SEPARATOR . 'Models'];

        // Get additional annotation directories from bootstrap classes
        $packageDirectories = $this->bootConfigurationManager->getEntityDirectories();

        return array_merge($directories, $packageDirectories);
    }

    /**
     * Return all directories used for reading annotations.
     * @return array<string>
     */
    public function getReadAnnotationDirectories(): array
    {
        return $this->annotationDirectories;
    }

    /**
     * Adds console commands to a given console application from bootstrapping packages.
     * @param Application $application
     */
    public function addDaenerysCommands(Application $application)
    {
        $this->bootConfigurationManager->addDaenerysCommands($this->game, $application);
    }
}
