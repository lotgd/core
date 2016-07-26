<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;
use Monolog\Logger;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Exceptions\InvalidConfigurationException;

class Bootstrap
{
    private $game;
    
    /**
     * Create a new Game object, with all the necessary configuration.
     * @throws InvalidConfigurationException
     * @return Game The newly created Game object.
     */
    public static function createGame(): Game
    {
        $boot = new self();
        
        return $boot->getGame();
    }
    
    public function getGame(): Game
    {
        if (is_null($this->game)) {
            $config = $this->createConfiguration();
            $logger = $this->createLogger($config);
            $entityManager = $this->createEntityManager($config);
        
            $this->game = new Game($config, $entityManager, $logger);
        }
        
        return $this->game;
    }
    
    /**
     * Creates a configuration object
     * @return \LotGD\Core\Configuration
     * @throws InvalidConfigurationException
     */
    protected function createConfiguration(): Configuration
    {
        // Get the path of LOTGD_CONFIG from the environmental variables
        $configFilePath = getenv('LOTGD_CONFIG');
        if ($configFilePath === false || strlen($configFilePath) == 0 || is_file($configFilePath) === false) {
            throw new InvalidConfigurationException("Invalid or missing configuration file: '{$configFilePath}'.");
        }
        
        return new Configuration($configFilePath);
    }
    
    /**
     * Creates a logger object
     * @return Logger
     */
    protected function createLogger(Configuration $config): Logger
    {
        $logger = new Logger('lotgd');
        
        // Add lotgd as the prefix for the log filenames.
        $logger->pushHandler(new \Monolog\Handler\RotatingFileHandler($config->getLogPath() . DIRECTORY_SEPARATOR . 'lotgd', 14));

        $v = Game::getVersion();
        $logger->info("Bootstrap constructing game (Daenerys 🐲{$v}).");
        
        return $logger;
    }
    
    /**
     * Creates an entity manager and connects to the database
     * @param \LotGD\Core\Configuration $config
     */
    protected function createEntityManager(Configuration $config): EntityManager
    {
        // Connect to the database using credentials from config
        $pdo = new \PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());
        
        $entityPaths = $this->getEntityPaths($config);
        $entityManager = $this->createEntityManagerInstance($pdo, $entityPaths);
        
        return $entityManager;
    }
    
    protected function getEntityPaths(Configuration $config): array
    {
        $directories = [
            __DIR__ . '/Models',
        ];
        
        if ($config->hasCrateBootstrapClass() && $config->getCrateBootstrapClass()->hasEntityPath()) {
            $directories[] = $config->getCrateBootstrapClass()->getEntityPath();
        }
        
        return $directories;
    }
    
    protected function createEntityManagerInstance(\PDO $pdo, array $paths = [])
    {        
        $configuration = Setup::createAnnotationMetadataConfiguration($paths, true);

        // Set a quote
        $configuration->setQuoteStrategy(new AnsiQuoteStrategy());

        $entityManager = EntityManager::create(["pdo" => $pdo], $configuration);

        // Create Schema
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);
        
        return $entityManager;
    }
    
    public static function bootstrapModules(Game $game)
    {
        /*$entityDirectories = self::getEntityDirectories($game->getConfiguration());
        
        // Bootstrap modules        
        foreach($game->getModuleManager()->getModules() as $module) {
            // ToDo: Fetch module
        }
        
        // Update database annotations
        $em = $game->getEntityManager();
        $em->flush();
        $em->close();
        
        // Get Connection
        $pdo = $em->getConnection()->getWrappedConnection();
        
        // Create NEW entity Manager
        $em = self::_createEntityManager($pdo, $entityDirectories);
        
        // Set NEW entity Manager
        $game->setEntityManager($em);*/
    }
}
