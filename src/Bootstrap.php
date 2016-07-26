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

        // Find other annotation directories from installed packages.
        $packages = $manager->getPackages();
        foreach ($packages as $p) {
            $name = $p->getName();
            $extra = $p->getExtra();
            if (!empty($extra['lotgd-namespace'])) {
                $namespace = $extra['lotgd-namespace'];
                $path = $manager->translateNamespaceToPath($namespace);

                if ($path === null) {
                    throw new \Exception("Cannot load classes in the namespace {$namespace} from package {$name}.");
                }

                $directories[] = $path;
            }
        }
        return $directories;
    }
}
