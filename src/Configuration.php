<?php
declare(strict_types=1);

namespace LotGD\Core;

use DateTime;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use LotGD\Core\Exceptions\InvalidConfigurationException;

/**
 * The configuration information for a LotGD game. Configuration is read from
 * a YAML file, who's path is specified when you construct the object.
 */
class Configuration
{
    private $databaseDSN;
    private $databaseName;
    private $databaseUser;
    private $databasePassword;
    private $logPath;
    private $gameEpoch;
    private $gameOffsetSeconds;
    private $gameDaysPerDay;

    /**
     * Create the configuration object, reading from the specified path.
     * @param string $configFilePath Path to a configuration YAML, relative to
     * the current working directory.
     */
    public function __construct(string $configFilePath)
    {
        try {
            $rawConfig = Yaml::parse(file_get_contents($configFilePath));
        } catch (ParseException $e) {
            $m = $e->getMessage();
            throw new InvalidConfigurationException("Unable to parse configuration file at {$configFilePath}: {$m}");
        }

        // Log dir path is relative to config directory.
        $logPath = $rawConfig['logs']['path'] ?? '';
        $realLogPath = dirname($configFilePath) . DIRECTORY_SEPARATOR . $logPath;
        if ($realLogPath === false || strlen($realLogPath) == 0 || is_dir($realLogPath) === false) {
            throw new InvalidConfigurationException("Invalid or missing log path: {$realLogPath}");
        }
        $this->logPath = $realLogPath;

        $dsn = $rawConfig['database']['dsn'] ?? '';
        $user = $rawConfig['database']['user'] ?? '';
        $passwd = $rawConfig['database']['password'] ?? '';
        $name = $rawConfig['database']['name'] ?? '';

        if ($dsn === false || strlen($dsn) == 0) {
            $m = "Invalid or missing data source name: {$dsn}";
            $logger->critical($m);
            throw new InvalidConfigurationException($m);
        }
        if ($user === false || strlen($user) == 0) {
            $m = "Invalid or missing database user: {$user}";
            $logger->critical($m);
            throw new InvalidConfigurationException("Invalid or missing database user: {$user}");
        }
        if ($passwd === false) {
            $m = "Invalid or missing database password: {$passwd}";
            $logger->critical($m);
            throw new InvalidConfigurationException("Invalid or missing database password: {$passwd}");
        }
        if ($name === false) {
            $m = "Invalid or missing database name: {$name}";
            $logger->critical($m);
            throw new InvalidConfigurationException("Invalid or missing database name: {$name}");
        }

        $this->databaseDSN = $dsn;
        $this->databaseUser = $user;
        $this->databasePassword = $passwd;
        $this->databaseName = $name;

        $gameEpoch = $rawConfig['game']['epoch'];
        $gameOffsetSeconds = $rawConfig['game']['offsetSeconds'];
        $gameDaysPerDay = $rawConfig['game']['daysPerDay'];

        $now = new DateTime();

        if ($now->getTimestamp() < $gameEpoch) {
            throw new InvalidConfigurationException("Game epoch is set in the future: {$gameEpoch}");
        }
        if ($gameOffsetSeconds < 0) {
            throw new InvalidConfigurationException("Game offset (in seconds) cannot be negative: {$gameOffsetSeconds}");
        }
        if ($gameDaysPerDay < 0) {
            throw new InvalidConfigurationException("Game days per day cannot be negative: {$gameDaysPerDay}");
        }

        $this->gameEpoch = $gameEpoch;
        $this->gameOffsetSeconds = $gameOffsetSeconds;
        $this->gameDaysPerDay = $gameDaysPerDay;
    }

    /**
     * Return the data source name, a way to describe where the database is. See
     * https://en.wikipedia.org/wiki/Data_source_name.
     * @return string The configured data source name.
     */
    public function getDatabaseDSN(): string
    {
        return $this->databaseDSN;
    }

    /**
     * Return the database name.
     * @return string The configured database name.
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Return the database user.
     * @return string The configured database user.
     */
    public function getDatabaseUser(): string
    {
        return $this->databaseUser;
    }

    /**
     * Return the database password.
     * @return string The configured database password.
     */
    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }

    /**
     * Return the path to the directory to store log files.
     * @return string The configured log directory path.
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }

    /**
     * Return which day, in real time, the game's date should start.
     * @return DateTime
     */
    public function getGameEpoch(): DateTime
    {
        return $this->gameEpoch;
    }

    /**
     * Return the offset, in seconds, from the game epoch, to define when the
     * game should start.
     * @return int
     */
    public function getGameOffsetSeconds(): int
    {
        return $this->gameOffsetSeconds;
    }

    /**
     * Return how many game days should exist inside one real time day.
     * @return int
     */
    public function getGameDaysPerDay(): int
    {
        return $this->gameDaysPerDay;
    }

    public function __toString(): string
    {
        $s = "";

        $s .= "database:\n";
        $s .= "  dsn: " . $this->getDatabaseDSN() . "\n";
        $s .= "  name: " . $this->getDatabaseName() . "\n";
        $s .= "  user: " . $this->getDatabaseUser() . "\n";
        $s .= "  password: <hidden>\n";
        $s .= "game:\n";
        $s .= "  epoch: " . $this->getGameEpoch() . "\n";
        $s .= "  offsetSeconds: " . $this->getGameOffsetSeconds() . "\n";
        $s .= "  daysPerDay: " . $this->getGameDaysPerDay() . "\n";
        $s .= "logs:\n";
        $s .= "  path: " . $this->getLogPath() . "\n";

        return $s;
    }
}
