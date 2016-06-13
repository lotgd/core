<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Bootstrap;
use LotGD\Core\Tests\AdditionalEntities\UserEntity;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testGame()
    {
        $g = Bootstrap::createGame();
        $this->assertNotNull($g->getEntityManager());
        $this->assertNotNull($g->getEventManager());
    }
    
    public function testDoctrineReadsAnnotationsFromAdditionalMetaDataDirectory()
    {
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
}
