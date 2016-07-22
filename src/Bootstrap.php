<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Exceptions\InvalidConfigurationException;

class Bootstrap
{
    private static $annotationMetaDataDirectories = [];

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
        $configFilePath = getenv('LOTGD_CONFIG');
        if ($configFilePath === false || strlen($configFilePath) == 0 || is_file($configFilePath) === false) {
            throw new InvalidConfigurationException("Invalid or missing configuration file: '{$configFilePath}'.");
        }
        $config = new Configuration($configFilePath);

        $logger = new \Monolog\Logger('lotgd');
        // Add lotgd as the prefix for the log filenames.
        $logger->pushHandler(new \Monolog\Handler\RotatingFileHandler($config->getLogPath() . DIRECTORY_SEPARATOR . 'lotgd', 14));

        $v = Game::getVersion();
        $logger->info("Bootstrap constructing game (Daenerys 🐲{$v}).");

        $pdo = new \PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());

        // Read db annotations from model files
        $annotationMetaDataDirectories = array_merge(
            [__DIR__ . '/Models'],
            self::$annotationMetaDataDirectories
        );
        $configuration = Setup::createAnnotationMetadataConfiguration($annotationMetaDataDirectories, true);

        // Set a quote
        $configuration->setQuoteStrategy(new AnsiQuoteStrategy());

        $entityManager = EntityManager::create(["pdo" => $pdo], $configuration);

        // Create Schema
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);

        $eventManager = new EventManager($entityManager);

        return new Game($config, $entityManager, $eventManager, $logger);
    }
}
