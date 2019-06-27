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
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    private $logger;

    public function setUp(): void
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

    public function testBootstrapLoadsPackageModels()
    {
        $installationManager = $this->getMockBuilder(InstallationManager::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstallPath"])
            ->getMock();
        $installationManager->method("getInstallPath")->willReturn(__DIR__ . "/FakeModule");

        $composer = $this->getMockBuilder(\Composer\Composer::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstallationManager"])
            ->getMock();
        $composer->method("getInstallationManager")->willReturn($installationManager);

        $fakeModulePackage = $this->getMockBuilder(AliasPackage::class)
            ->disableOriginalConstructor()
            ->setMethods(["getType", "getAutoload"])
            ->getMock();
        $fakeModulePackage->method("getType")->willReturn("lotgd-module");
        $fakeModulePackage->method("getAutoload")->willReturn([
            "psr-4" => [
                "LotGD\\Core\\Tests\\FakeModule\\" => "FakeModule/"
            ]
        ]);

        $composerManager = $this->getMockBuilder(ComposerManager::class)
            ->disableOriginalConstructor()
            ->setMethods(["getPackages", "getComposer", "translateNamespaceToPath"])
            ->getMock();
        $composerManager->method("getPackages")->willReturn([$fakeModulePackage]);
        $composerManager->method("getComposer")->willReturn($composer);
        $composerManager
            ->expects($this->exactly(1))
            ->method("translateNamespaceToPath")
            ->with("LotGD\\Core\\Tests\\FakeModule\\Models\\")
            ->willReturn(__DIR__ . "/FakeModule/Models");

        $bootstrap = $this->getMockBuilder(Bootstrap::class)
            ->setMethods(["createComposerManager"])
            ->getMock();
        $bootstrap->method("createComposerManager")->willReturn($composerManager);

        // run tests
        $game = $bootstrap->getGame(implode(DIRECTORY_SEPARATOR, [__DIR__, '..']));

        $user = new UserEntity();
        $user->setName("Monthy");
        $game->getEntityManager()->persist($user);
        $game->getEntityManager()->flush();
        $id = $user->getId();
        $this->assertIsInt($id);
        $game->getEntityManager()->clear();
        $user = $game->getEntityManager()->getRepository(UserEntity::class)->find($id);
        $this->assertIsInt($user->getId());
        $this->assertIsSTring($user->getName());
        $this->assertSame("Monthy", $user->getName());
    }

    public function testIfGameAwareEntitiesHaveAGAmeInstanceAssociatedAfterLoading()
    {
        $installationManager = $this->getMockBuilder(InstallationManager::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstallPath"])
            ->getMock();
        $installationManager->method("getInstallPath")->willReturn(__DIR__ . "/FakeModule");

        $composer = $this->getMockBuilder(\Composer\Composer::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstallationManager"])
            ->getMock();
        $composer->method("getInstallationManager")->willReturn($installationManager);

        $fakeModulePackage = $this->getMockBuilder(AliasPackage::class)
            ->disableOriginalConstructor()
            ->setMethods(["getType", "getAutoload"])
            ->getMock();
        $fakeModulePackage->method("getType")->willReturn("lotgd-module");
        $fakeModulePackage->method("getAutoload")->willReturn([
            "psr-4" => [
                "LotGD\\Core\\Tests\\FakeModule\\" => "FakeModule/"
            ]
        ]);

        $composerManager = $this->getMockBuilder(ComposerManager::class)
            ->disableOriginalConstructor()
            ->setMethods(["getPackages", "getComposer", "translateNamespaceToPath"])
            ->getMock();
        $composerManager->method("getPackages")->willReturn([$fakeModulePackage]);
        $composerManager->method("getComposer")->willReturn($composer);
        $composerManager
            ->expects($this->exactly(1))
            ->method("translateNamespaceToPath")
            ->with("LotGD\\Core\\Tests\\FakeModule\\Models\\")
            ->willReturn(__DIR__ . "/FakeModule/Models");

        $bootstrap = $this->getMockBuilder(Bootstrap::class)
            ->setMethods(["createComposerManager"])
            ->getMock();
        $bootstrap->method("createComposerManager")->willReturn($composerManager);

        // run tests
        $game = $bootstrap->getGame(implode(DIRECTORY_SEPARATOR, [__DIR__, '..']));

        // A freshly created user entity should not rely
        $user = new UserEntity();
        $user->setName("Testus");
        $user->setGame($game);

        $this->assertSame($game, $user->returnGame());

        $game->getEntityManager()->persist($user);
        $game->getEntityManager()->flush();
        $id = $user->getId();
        $this->assertIsInt($id);
        $game->getEntityManager()->clear();
        $user = $game->getEntityManager()->getRepository(UserEntity::class)->find($id);

        $this->assertSame($game, $user->returnGame());
        $this->assertSame([$user->getName()], $user->getNameAsArray());
    }
}
