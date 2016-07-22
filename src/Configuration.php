<?php
declare(strict_types=1);

namespace LotGD\Core;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use LotGD\Core\Exceptions\InvalidConfigurationException;

class Configuration
{
    private $databaseDSN;
    private $databaseName;
    private $databaseUser;
    private $databasePassword;
    private $logPath;

    public function __construct(string $configFilePath)
    {
        try {
            $rawConfig = Yaml::parse(file_get_contents($configFilePath));
        } catch (ParseException $e) {
            $m = $e->getMessage();
            throw new InvalidConfigurationException("Unable to parse configuration file at '{$configFilePath}': {$m}.");
        }

        // Log dir path is relative to config directory.
        $logPath = $rawConfig['logs']['path'] ?? '';
        $realLogPath = dirname($configFilePath) . DIRECTORY_SEPARATOR . $logPath;
        if ($realLogPath === false || strlen($realLogPath) == 0 || is_dir($realLogPath) === false) {
            throw new InvalidConfigurationException("Invalid or missing log path: '{$realLogPath}'.");
        }
        $this->logPath = $realLogPath;

        $dsn = $rawConfig['database']['dsn'] ?? '';
        $user = $rawConfig['database']['user'] ?? '';
        $passwd = $rawConfig['database']['password'] ?? '';
        $name = $rawConfig['database']['name'] ?? '';

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
        if ($name === false) {
            $m = "Invalid or missing database name: '{$name}'";
            $logger->critical($m);
            throw new InvalidConfigurationException("Invalid or missing database name: '{$name}'");
        }

        $this->databaseDSN = $dsn;
        $this->databaseUser = $user;
        $this->databasePassword = $passwd;
        $this->databaseName = $name;
    }

    public function getDatabaseDSN(): string
    {
        return $this->databaseDSN;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getDatabaseUser(): string
    {
        return $this->databaseUser;
    }

    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }
}
