<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Bootstrap;
use LotGD\Core\BootstrapInterface;
use LotGD\Core\Tests\AdditionalEntities\UserEntity;

class BootstrapCrate implements BootstrapInterface {
    public function hasEntityPath(): bool { return true; }
    public function getEntityPath(): string { return __DIR__ . "/AdditionalEntities"; }
}

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testGame()
    {
        $g = Bootstrap::createGame();
        $this->assertNotNull($g->getEntityManager());
        $this->assertNotNull($g->getEventManager());
        $this->assertNotNull($g->getLogger());
    }
    
    public function testUserEntityDoesThrowDBALException() {
        $this->expectException(\Doctrine\DBAL\DBALException::class);
        
        $game = Bootstrap::createGame();
        
        $user = new UserEntity();
        $user->setName("Monthy");
        
        $game->getEntityManager()->persist($user);
        $game->getEntityManager()->flush();
    }
    
    public function testBootstrappedCrateDoesExtendDoctrine() {
        $mockBootstrap = $this->getMockBuilder("\LotGD\Core\Bootstrap")
            ->setMethods(["createConfiguration"])
            ->getMock();
        
        $mockConfiguration = $this->getMockBuilder("\LotGD\Core\Configuration")
            ->setConstructorArgs([getenv("LOTGD_CONFIG")])
            ->setMethods(["hasCrateBootstrapClass", "getCrateBootstrapClass"])
            ->getMock();
        
        $bootstrapClass = BootstrapCrate::class;
        
        $mockConfiguration->expects($this->any())
            ->method("hasCrateBootstrapClass")
            ->willReturn(true);
        $mockConfiguration->expects($this->any())
            ->method("getCrateBootstrapClass")
            ->willReturn(new $bootstrapClass());
        
        $mockBootstrap->expects($this->any())
            ->method("createConfiguration")
            ->willReturn($mockConfiguration);
        
        $game = $mockBootstrap->getGame();
        
        Bootstrap::bootstrapModules($game);
        
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
