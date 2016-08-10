<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Composer\Package\PackageInterface;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\ComposerManager;
use LotGD\Core\Tests\FakeModule\UserEntity;

class ComposerManagerTest extends \PHPUnit_Framework_TestCase
{
    private $logger;

    public function setUp()
    {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
    }

    public function testTranslateNamespaceToPath()
    {
        $manager = new ComposerManager(implode(DIRECTORY_SEPARATOR, [__DIR__, '..']));

        $namespace = 'LotGD\\Core\\Tests\\';
        $this->assertEquals(__DIR__, $manager->translateNamespaceToPath($namespace));

        $namespace = '\\LotGD\\Core\\Tests\\';
        $this->assertEquals(__DIR__, $manager->translateNamespaceToPath($namespace));

        $namespace = 'LotGD\\Core\\Tests';
        $this->assertEquals(__DIR__, $manager->translateNamespaceToPath($namespace));

        $namespace = 'LotGD\\Core\\Tests\\FakeModule';
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'FakeModule', $manager->translateNamespaceToPath($namespace));

        $namespace = 'LotGD\\NotFound';
        $this->assertNull($manager->translateNamespaceToPath($namespace));
    }
}
