<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Exceptions\InvalidConfigurationException;

class Bootstrap
{
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

        $logger = new Logger('lotgd');
        // Add lotgd as the prefix for the log filenames.
        $logger->pushHandler(new RotatingFileHandler($config->getLogPath() . DIRECTORY_SEPARATOR . 'lotgd', 14));

        $v = Game::getVersion();
        $logger->info("Bootstrap constructing game (Daenerys 🐲{$v}).");

        $pdo = new \PDO($config->getDatabaseDSN(), $config->getDatabaseUser(), $config->getDatabasePassword());

        $configuration = Setup::createAnnotationMetadataConfiguration(Bootstrap::generateAnnotationDirectories($logger, new ComposerManager($logger)), true);

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

    public static function generateAnnotationDirectories(Logger $logger, ComposerManager $manager): array
    {
        // Read db annotations from our own model files.
        $directories = [__DIR__ . '/Models'];

        // Find other annotation directories from installed modules.
        $modulePackages = $manager->getModulePackages();
        foreach ($modulePackages as $p) {
            $name = $p->getName();
            $extra = $p->getExtra();
            if (!empty($extra['lotgd-namespace'])) {
                $n = $extra['lotgd-namespace'];

                // Find the directory for this namespace by using the autoloader
                // to find the required Module class.
                $autoloader = require(ComposerManager::findAutoloader());
                $path = $autoloader->findFile($n . 'Module');
                if ($path === false) {
                    $logger->error("Module {$name} lacks a {$n}Module class.");
                    continue;
                }

                $directories[] = dirname($path);
            } else {
                $logger->error("Module {$name} lacks a 'lotgd-namespace' entry in its composer 'extra' field. Its database models will not be properly loaded.");
            }
        }
        return $directories;
    }
}
