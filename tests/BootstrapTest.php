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
        $package->method('getName')->willReturn('lotgd/BootstrapTest');
        $package->method('getExtra')->willReturn(array(
            'lotgd-namespace' => 'LotGD\\Core\\Tests\\FakeModule\\',
        ));
        $composerManager->method('getPackages')->willReturn(array($package));

        $expected = __DIR__ . DIRECTORY_SEPARATOR . 'FakeModule';
        $composerManager->method('translateNamespaceToPath')->willReturn($expected);

        $result = Bootstrap::generateAnnotationDirectories($this->logger, $composerManager);

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
