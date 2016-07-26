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
    Exceptions\ArgumentException,
    Exceptions\InvalidConfigurationException
};

class Bootstrap
{
    private $game;
    private $bootstrapClasses = [];
    private $annotationDirectories = [];
    
    /**
     * Create a new Game object, with all the necessary configuration.
     * @throws InvalidConfigurationException
     * @return Game The newly created Game object.
     */
    public static function createGame(): Game
    {
        $game = new self();
        return $game->getGame();
    }
    
    public function getGame()
    {
        $config = $this->createConfiguration();
        $logger = $this->createLogger($config, "lotgd");
        $composer = $this->createComposer($logger);
        $this->bootstrapClasses = $this->getBootstrapClasses($composer);
        
        $pdo = $this->connectToDatabase($config);
        $entityManager = $this->createEntityManager($pdo);
        
        $eventManager = $this->createEventManager($entityManager);
        
        $this->game = new Game($config, $logger, $entityManager, $eventManager);
        
        return $this->game;
    }
    
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
    protected function createComposer(Logger $logger): ComposerManager
    {
        $composer = new ComposerManager($logger);
        
        return $composer;
    }
    
    /**
     * Returns all bootstrap classes
     * @param ComposerManager $composer
     * @return array
     * @throws \Exception
     */
    protected function getBootstrapClasses(ComposerManager $composer): array
    {
        $packages = $composer->getPackages();
        $classes = [];
        
        foreach ($packages as $package) {
            if (isset($package->getExtra()["lotgd-namespace"]) === false) {
                continue;
            }
            
            $cn = $package->getExtra()["lotgd-namespace"] . "Bootstrap";
            
            // silently ignore that class does not exist, could be one that doesn't need to bootstrap
            if (class_exists($cn, true) === false) {
                continue;
            }
            
            $cl = new $cn();
            
            if ($cl instanceof BootstrapInterface) {
                $classes[] = $cl;
            }
            else {
                $name = $package->getName() . "@" . $package->getVersion();
                throw new \Exception("Package {$name} does not implement BootstrapInterface in it's Bootstrap class");
            }
        }
        
        return $classes;
    }
    
    /**
     * Returns a configuration object reading from LOTGD_CONFIG
     * @return \LotGD\Core\Configuration
     * @throws InvalidConfigurationException
     */
    protected function createConfiguration(): Configuration
    {
        $configFilePath = getenv('LOTGD_CONFIG');
        if ($configFilePath === false || strlen($configFilePath) == 0 || is_file($configFilePath) === false) {
            throw new InvalidConfigurationException("Invalid or missing configuration file: '{$configFilePath}'.");
        }
        
        $config = new Configuration($configFilePath);
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
     * Creates and returns an instance of the EventManager
     * @param EntityManagerInterface $entityManager
     * @return \LotGD\Core\EventManager
     */
    protected function createEventManager(EntityManagerInterface $entityManager): EventManager
    {
        return new EventManager($entityManager);
    }

    /**
     * Is used to get all directories used to generate annotations.
     * @param array $bootstrapClasses
     * @return array
     */
    protected function generateAnnotationDirectories(): array
    {
        // Read db annotations from our own model files.
        $directories = [__DIR__ . '/Models'];
        
        // Get additional annotation directories from bootstrap classes
        foreach ($this->bootstrapClasses as $bootstrap) {
            if ($bootstrap->hasEntityPath()) {
                $directories[] = $bootstrap->getEntityPath();
            }
        }
        
        return $directories;
    }
    
    public function getReadAnnotationDirectories(): array
    {
        return $this->annotationDirectories;
    }
    
    public function addDaenerysCommands(Application $application)
    {
        foreach($this->bootstrapClasses as $bootstrap)
        {
            $bootstrap->addDaenerysCommand($this->game, $application);
        }
    }
}
