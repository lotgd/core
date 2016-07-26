<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Composer\Package\PackageInterface;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\Bootstrap;
use LotGD\Core\ComposerManager;
use LotGD\Core\Tests\FakeModule\Models\UserEntity;

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
        
        $bootstrap = $this->getMockBuilder(Bootstrap::class)
            ->setMethods(["createComposer"])
            ->getMock();
        
        $bootstrap->method("createComposer")->willReturn($composerManager);
        
        $game = $bootstrap->getGame();
        
        $this->assertGreaterThanOrEqual(2, $bootstrap->getReadAnnotationDirectories());
        
        $user = new UserEntity();
        $user->setName("Monthy");
        $game->getEntityManager()->persist($user);
        $game->getEntityManager()->flush();
        $id = $user->getId();
        $this->assertInternalType("int", $id);
        $game->getEntityManager()->clear();
        $user = $game->getEntityManager()->getRepository(UserEntity::class)->find($id);
        $this->assertInternalType("int", $user->getId());
        $this->assertInternalType("string", $user->getName());
        $this->assertSame("Monthy", $user->getName());
    }
}
