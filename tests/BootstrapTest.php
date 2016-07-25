<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Composer\Package\PackageInterface;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\Bootstrap;
use LotGD\Core\ComposerManager;
use LotGD\Core\Tests\FakeModule\UserEntity;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    private $logger;

    public function setUp()
    {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
    }

    public function testGame()
    {
        $g = Bootstrap::createGame();
        $this->assertNotNull($g->getEntityManager());
        $this->assertNotNull($g->getEventManager());
        $this->assertNotNull($g->getLogger());
    }

    public function testGenerateAnnotationDirectories()
    {
        $composerManager = $this->getMockBuilder(ComposerManager::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $package = $this->getMockForAbstractClass(PackageInterface::class);
        $package->method('getExtra')->willReturn(array(
            'lotgd-namespace' => 'LotGD\\Core\\Tests\\FakeModule\\',
        ));

        $composerManager->method('getModulePackages')->willReturn(array($package));

        $result = Bootstrap::generateAnnotationDirectories($this->logger, $composerManager);
        $expected = __DIR__ . DIRECTORY_SEPARATOR . 'FakeModule';

        $string = implode(', ', $result);
        $found = false;
        foreach ($result as $r) {
            if (realpath($r) == $expected) {
                $found = true;
            }
        }
        $this->assertTrue($found, "Annotation directories [{$string}] does not contain {$expected}.");
    }
}
