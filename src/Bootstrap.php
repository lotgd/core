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
        $logPath = getenv('LOG_PATH');
        if ($logPath === false || strlen($logPath) == 0 || is_dir($logPath) === false) {
            throw new InvalidConfigurationException("Invalid or missing log directory: '{$logPath}'");
        }
        $cleanedLogPath = realpath($logPath);
        $logger = new \Monolog\Logger('lotgd');
        // Add lotgd as the prefix for the log filenames.
        $logger->pushHandler(new \Monolog\Handler\RotatingFileHandler($cleanedLogPath . DIRECTORY_SEPARATOR . 'lotgd', 14));

        $v = Game::getVersion();
        $logger->info("Bootstrap constructing game (Daenerys 🐲 {$v}).");

        $dsn = getenv('DB_DSN');
        $user = getenv('DB_USER');
        $passwd = getenv('DB_PASSWORD');

        if ($dsn === false || strlen($dsn) == 0) {
            $m = "Invalid or missing data source name: '{$dsn}'";
            $logger->critical($m);
            throw new InvalidConfigurationException($m);
        }
        if ($user === false || strlen($user) == 0) {
            $m = "Invalid or missing database user: '{$user}'";
            $logger->critical($m);
            throw new InvalidConfigurationException("Invalid or missing database user: '{$user}'");
        }
        if ($passwd === false) {
            $m = "Invalid or missing database password: '{$passwd}'";
            $logger->critical($m);
            throw new InvalidConfigurationException("Invalid or missing database password: '{$passwd}'");
        }

        $pdo = new \PDO($dsn, $user, $passwd);

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

        return new Game($entityManager, $eventManager, $logger);
    }
}
