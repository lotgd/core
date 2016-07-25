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

    public function testDoctrineReadsAnnotationsFromAdditionalMetaDataDirectory()
    {
        Bootstrap::clear();
        Bootstrap::registerAnnotationMetaDataDirectory(__DIR__ . "/AdditionalEntities");

        $g = Bootstrap::createGame();

        $user = new UserEntity();
        $user->setName("Monthy");

        $g->getEntityManager()->persist($user);
        $g->getEntityManager()->flush();

        $id = $user->getId();
        $this->assertInternalType("int", $id);

        $g->getEntityManager()->clear();
        $user = $g->getEntityManager()->getRepository(UserEntity::class)->find($id);

        $this->assertInternalType("int", $user->getId());
        $this->assertInternalType("string", $user->getName());
        $this->assertSame("Monthy", $user->getName());
    }
    
    public function testUserEntityDoesThrowDBALException() {
        $this->expectException(\Doctrine\DBAL\DBALException::class);
                
        Bootstrap::clear();
        
        $game = Bootstrap::createGame();
        
        $user = new UserEntity();
        $user->setName("Monthy");
        
        $game->getEntityManager()->persist($user);
        $game->getEntityManager()->flush();
    }
    
    public function testBootstrappedCrateDoesExtendDoctrine() {
        Bootstrap::clear();
        Bootstrap::registerCrateBootstrap(new BootstrapCrate());
        
        $game = Bootstrap::createGame();
        
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
