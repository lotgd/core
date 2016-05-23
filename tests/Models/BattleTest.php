<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\{
    Battle,
    Models\Character,
    Models\Monster
};

use LotGD\Core\Tests\ModelTestCase;

/**
 * Tests the management of Characters
 */
class BattleTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "battle";
    
    public function testBasicMonster()
    {
        $em = $this->getEntityManager();
        
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $this->assertSame(5, $monster->getLevel());
        $this->assertSame(52, $monster->getMaxHealth());
        $this->assertSame(9, $monster->getAttack());
        $this->assertSame(7, $monster->getDefense());
        $this->assertSame($monster->getMaxHealth(), $monster->getHealth());
    }
    
    public function testBattle()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $battle = new Battle($character, $monster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $character->getHealth();
            $oldMonsterHealth = $monster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $character->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $monster->getHealth());
            
            if ($character->isAlive() === false && $monster->isAlive() === false) {
                break;
            }
        }
        
        $this->assertTrue($character->isAlive() xor $monster->isAlive());
    }
}
