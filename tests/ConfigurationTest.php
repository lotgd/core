<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use DateTime;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\Configuration;
use LotGD\Core\Exceptions\InvalidConfigurationException;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $logger;
    private $configDir;

    public function setUp()
    {
        $this->configDir = __DIR__ . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, ['datasets', 'config']);

        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
    }

    public function testBasicConfiguration()
    {
        $configuration = new Configuration($this->configDir . DIRECTORY_SEPARATOR . 'basic.yml');

        $this->assertEquals('some_dsn', $configuration->getDatabaseDSN());
        $this->assertEquals('some_name', $configuration->getDatabaseName());
        $this->assertEquals('some_user', $configuration->getDatabaseUser());
        $this->assertEquals('some_password', $configuration->getDatabasePassword());
        $this->assertEquals($this->configDir . DIRECTORY_SEPARATOR . './', $configuration->getLogPath());
        $this->assertEquals(new DateTime('2016-07-01 01:01:01.0 -8'), $configuration->getGameEpoch());
        $this->assertEquals(32, $configuration->getGameOffsetSeconds());
        $this->assertEquals(2, $configuration->getGameDaysPerDay());
    }

    public function testToString()
    {
        $configuration = new Configuration($this->configDir . DIRECTORY_SEPARATOR . 'basic.yml');
        $s = $configuration->__toString();

        $this->assertFalse(strpos($s, 'some_password'));
    }
    
    /**
     * Returns a Mocked Configuration instance using default values that does not relay
     * on a file.
     * @param array $config configuration that is merged to the default configuration
     * @return Configuration Mocked Configuration class
     */
    private function getPseudoConfiguration(array $config)
    {
        $basicConfig = [
            "database" => [
                "dsn" => "some_dsn",
                "user" => "some_user",
                "password" => "some_password",
                "name" => "some_name"
            ],
            "game" => [
                "epoch" => 1467334861,
                "offsetSeconds" => 32,
                "daysPerDay" => 2
            ],
            "logs" => [
                "path" => "./"
            ]
        ];
        
        $config = array_merge($basicConfig, $config);
        
        $configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->setMethods(["retrieveRawConfig"])
            ->getMock();
        
        $configuration->method("retrieveRawConfig")->willReturn($config);
        
        $configuration->__construct("dummy");
        return $configuration;
    }
    
    public function testCWDParsingForSQLiteDatabaseDSN()
    {
        $rawDSN = "sqlite:%cwd%db.db3";
                
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "dsn" => $rawDSN,
                "user" => "some_user",
                "password" => "some_password",
                "name" => "some_name"
            ],
        ]);
        
        list($dsn, $user, $password) = $configuration->getDatabaseConnectionDetails("/home/web/sqlite");
        
        $this->assertNotSame($rawDSN, $dsn);
        $this->assertSame("sqlite:/home/web/sqlite/db.db3", $dsn);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDatabaseDSNIsMissing()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Invalid or missing data source name: ");
        
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "user" => "some_user",
                "password" => "some_password",
                "name" => "some_name"
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDatabaseDSNIsFalse()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Invalid or missing data source name: ");
        
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "dsn" => false,
                "user" => "some_user",
                "password" => "some_password",
                "name" => "some_name"
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDatabaseUserIsMissing()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Invalid or missing database user: ");
        
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "dsn" => "some_dsn",
                "password" => "some_password",
                "name" => "some_name"
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDatabaseUserIsFalse()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Invalid or missing database user: ");
        
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "dsn" => "some_dsn",
                "user" => false,
                "password" => "some_password",
                "name" => "some_name"
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDatabasePasswordIsFalse()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Invalid or missing database password: ");
        
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "dsn" => "some_dsn",
                "user" => "some_user",
                "password" => false,
                "name" => "some_name"
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDatabaseNameIsFalse()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Invalid or missing database name: ");
        
        $configuration = $this->getPseudoConfiguration([
            "database" => [
                "dsn" => "some_dsn",
                "user" => "some_user",
                "password" => "some_password",
                "name" => false,
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfEpochIsInFuture()
    {
        $this->expectException(InvalidConfigurationException::class);
        
        $now = new DateTime();
        $timestamp = $now->getTimestamp() + 3600;
        
        $this->expectExceptionMessage("Game epoch is set in the future: {$timestamp}");
        
        $configuration = $this->getPseudoConfiguration([
            "game" => [
                "epoch" => $timestamp,
                "offsetSeconds" => 32,
                "daysPerDay" => 2
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfOffsetSecondsAreNegative()
    {
        $this->expectException(InvalidConfigurationException::class);        
        $this->expectExceptionMessage("Game offset (in seconds) cannot be negative: -32");
        
        $configuration = $this->getPseudoConfiguration([
            "game" => [
                "epoch" => 1467334861,
                "offsetSeconds" => -32,
                "daysPerDay" => 2
            ],
        ]);
    }
    
    public function testIfInvalidConfigurationExceptionIsThrownIfDaysPerDayIsNegative()
    {
        $this->expectException(InvalidConfigurationException::class);        
        $this->expectExceptionMessage("Game days per day cannot be negative: -2");
        
        $configuration = $this->getPseudoConfiguration([
            "game" => [
                "epoch" => 1467334861,
                "offsetSeconds" => 32,
                "daysPerDay" => -2
            ],
        ]);
    }
}
