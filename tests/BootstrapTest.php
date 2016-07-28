<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;
use Composer\Installer\InstallationManager;

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
    
    public function testWorkingFromChildWorkingDirectory()
    {
        $cwd = getcwd();
        $oldconf = getenv("LOTGD_CONFIG");
        chdir($cwd . "/tests/");
        putenv("LOTGD_CONFIG=../".$oldconf);
        
        $this->assertStringEndsWith("/tests", getcwd());
        $this->assertStringStartsWith(".././", getenv("LOTGD_CONFIG"));
        
        $game = Bootstrap::createGame();
        
        chdir($cwd);
        putenv("LOTGD_CONFIG=" . $oldconf);
        
        $this->assertStringEndsNotWith("/tests", getcwd());
        $this->assertStringStartsNotWith(".././", getenv("LOTGD_CONFIG"));
    }
    
    public function testBootstrapLoadsPackageModels()
    {
        $installationManager = $this->getMockBuilder(InstallationManager::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstallPath"])
            ->getMock();
        $installationManager->method("getInstallPath")->willReturn(__DIR__ . "/FakeModule");
        
        $composer = $this->getMockBuilder(\Composer\Composer::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstallationManager", "translateNamespaceToPath"])
            ->getMock();
        $composer->method("getInstallationManager")->willReturn($installationManager);
        $composer
            ->expects($this->exactly(1))
            ->method("translateNamespaceToPath")
            ->with("LotGD\\Core\\Tests\\FakeModule\\Models")
            ->willReturn(__DIR__ . "/FakeModule/Models");
        
        $fakeModulePackage = $this->getMockBuilder(AliasPackage::class)
            ->disableOriginalConstructor()
            ->setMethods(["getType", "getExtra"])
            ->getMock();
        $fakeModulePackage->method("getType")->willReturn("lotgd-module");
        $fakeModulePackage->method("getExtra")->willReturn(["lotgd-namespace" => "LotGD\\Core\\Tests\\FakeModule\\"]);
        
        $composerManager = $this->getMockBuilder(ComposerManager::class)
            ->setMethods(["getPackages", "getComposer"])
            ->getMock();
        $composerManager->method("getPackages")->willReturn([$fakeModulePackage]);
        $composerManager->method("getComposer")->willReturn($composer);
        
        $bootstrap = $this->getMockBuilder(Bootstrap::class)
            ->setMethods(["createComposerManager"])
            ->getMock();
        $bootstrap->method("createComposerManager")->willReturn($composerManager);
        
        // run tests
        $game = $bootstrap->getGame();
        
        $this->assertGreaterThanorEqual(3, $bootstrap->getReadAnnotationDirectories());
        
        /*$user = new UserEntity();
        $user->setName("Monthy");
        $game->getEntityManager()->persist($user);
        $game->getEntityManager()->flush();
        $id = $user->getId();
        $this->assertInternalType("int", $id);
        $game->getEntityManager()->clear();
        $user = $game->getEntityManager()->getRepository(UserEntity::class)->find($id);
        $this->assertInternalType("int", $user->getId());
        $this->assertInternalType("string", $user->getName());
        $this->assertSame("Monthy", $user->getName());*/
    }

    /*public function testGenerateAnnotationDirectories()
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
            ->setMethods(["createComposerManager"])
            ->getMock();
        
        $bootstrap->method("createComposerManager")->willReturn($composerManager);
        
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
    }*/
}
