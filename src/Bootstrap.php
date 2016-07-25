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
    private static $annotationMetaDataDirectories = [];
    private static $bootstrapCrate = null;
    
    public static function clear()
    {
        self::$annotationMetaDataDirectories = [];
        self::$bootstrapCrate = null;
    }
    
    public static function registerCrateBootstrap(BootstrapInterface $bootstrap)
    {
        self::$bootstrapCrate = $bootstrap;
    }

    public static function registerAnnotationMetaDataDirectory(string $directory)
    {
        if (is_dir($directory) === false) {
            throw new ArgumentException("{$directory} needs to be a valdid directory");
        }

        self::$annotationMetaDataDirectories[] = $directory;
    }

    /**
     * Create a new Game object, with all the necessary configuration.
     * @throws InvalidConfigurationException
     * @return Game The newly created Game object.
     */
    public static function createGame(): Game
    {
        $config = self::createConfiguration();
        
        // Depending on config
        $logger = self::createLogger($config);
        $entityManager = self::createEntityManager($config);
        
        // Create game
        $game = new Game($config, $entityManager, $logger);
        
        // Bootstrap modules
        self::bootstrapModules($game);

        return $game;
    }
    
    /**
     * Creates a configuration object
     * @return \LotGD\Core\Configuration
     * @throws InvalidConfigurationException
     */
    protected static function createConfiguration(): Configuration
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
    protected static function createLogger(Configuration $config): Logger
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
    protected static function createEntityManager(Configuration $config): EntityManager
    {
        // Doctrine
        $pdo = new \PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());

        $entityManager = self::_createEntityManager($pdo);
        
        return $entityManager;
    }
    
    protected static function _createEntityManager(\PDO $pdo, array $paths = [])
    {
        // Read db annotations from model files
        if (count($paths) === 0) {
            $annotationMetaDataDirectories = array_merge(
                [__DIR__ . '/Models'],
                self::$annotationMetaDataDirectories,
                $paths
            );
        }
        else {
            $annotationMetaDataDirectories = $paths;
        }
        
        $configuration = Setup::createAnnotationMetadataConfiguration($annotationMetaDataDirectories, true);

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
        $entityDirectories = array_merge(
            [__DIR__ . '/Models'],
            self::$annotationMetaDataDirectories
        );
        
        // Bootstrap crate first
        if (!is_null(self::$bootstrapCrate)) {
            if (self::$bootstrapCrate->hasEntityPath()) {
                $entityDirectories[] = self::$bootstrapCrate->getEntityPath();
            }
        }
        
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
        $game->setEntityManager($em);
    }
}
