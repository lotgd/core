<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\{
    Battle,
    DiceBag,
    Game,
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
    
    public function getMockGame(Character $character): Game
    {
        $game = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $game->method('getEntityManager')->willReturn($this->getEntityManager());
        $game->method('getDiceBag')->willReturn(new DiceBag());
        $game->method('getCharacter')->willReturn($character);
        
        return $game;
    }
    
    /**
     * Tests basic monster functionality
     */
    public function testBasicMonster()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $this->assertSame(5, $monster->getLevel());
        $this->assertSame(52, $monster->getMaxHealth());
        $this->assertSame(9, $monster->getAttack($this->getMockGame($character)));
        $this->assertSame(7, $monster->getDefense($this->getMockGame($character)));
        $this->assertSame($monster->getMaxHealth(), $monster->getHealth());
    }
    
    /**
     * Tests a fair fight between a monster and a player.
     */
    public function testFairBattle()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $battle = new Battle($this->getMockGame($character), $character, $monster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $character->getHealth();
            $oldMonsterHealth = $monster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $character->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $monster->getHealth());
            
            if ($battle->isOver()) {
                break;
            }
        }
        
        $this->assertTrue($battle->isOver());
        $this->assertTrue($character->isAlive() xor $monster->isAlive());
    }
    
    /**
     * Tests a fight which the player has to win (lvl 100 vs lvl 1)
     */
    public function testPlayerWinBattle()
    {
        $em = $this->getEntityManager();
        
        $highLevelPlayer = $em->getRepository(Character::class)->find(2);
        $lowLevelMonster = $em->getRepository(Monster::class)->find(3);
        
        $battle = new Battle($this->getMockGame($highLevelPlayer), $highLevelPlayer, $lowLevelMonster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $highLevelPlayer->getHealth();
            $oldMonsterHealth = $lowLevelMonster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $highLevelPlayer->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $lowLevelMonster->getHealth());
            
            if ($battle->isOver()) {
                break;
            }
        }
        
        $this->assertTrue($highLevelPlayer->isAlive());
        $this->assertFalse($lowLevelMonster->isAlive());
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getWinner(), $highLevelPlayer);
    }
    
    /**
     * Tests a fight which the player has to loose (lvl 1 vs lvl 100)
     */
    public function testPlayerLooseBattle()
    {
        $em = $this->getEntityManager();
        
        $lowLevelPlayer = $em->getRepository(Character::class)->find(3);
        $highLevelMonster = $em->getRepository(Monster::class)->find(2);
        
        $battle = new Battle($this->getMockGame($lowLevelPlayer), $lowLevelPlayer, $highLevelMonster);
        
        for ($n = 0; $n < 99; $n++) {
            $oldPlayerHealth = $lowLevelPlayer->getHealth();
            $oldMonsterHealth = $highLevelMonster->getHealth();
            
            $battle->fightNRounds(1);
            
            $this->assertLessThanOrEqual($oldPlayerHealth, $lowLevelPlayer->getHealth());
            $this->assertLessThanOrEqual($oldMonsterHealth, $highLevelMonster->getHealth());
            
            if ($battle->isOver()) {
                break;
            }
        }
        
        $this->assertFalse($lowLevelPlayer->isAlive());
        $this->assertTrue($highLevelMonster->isAlive());
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getWinner(), $highLevelMonster);
    }
    
    /**
     * @expectedException LotGD\Core\Exceptions\BattleNotOverException
     */
    public function testBattleNotOverExceptionFromWinner()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $battle = new Battle($this->getMockGame($character), $character, $monster);
        
        $battle->getWinner();
    }
    
    /**
     * @expectedException LotGD\Core\Exceptions\BattleNotOverException
     */
    public function testBattleNotOverExceptionFromLooser()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $battle = new Battle($this->getMockGame($character), $character, $monster);
        
        $battle->getLoser();
    }
    
    /**
     * Tests if the BattleIsOverException gets thrown.
     * @expectedException LotGD\Core\Exceptions\BattleIsOverException
     */
    public function testBattleIsOverException()
    {
        $em = $this->getEntityManager();
        
        $character = $em->getRepository(Character::class)->find(1);
        $monster = $em->getRepository(Monster::class)->find(1);
        
        $battle = new Battle($this->getMockGame($character), $character, $monster);
        
        // Fighting for 99 rounds should be enough for determining a looser - and to
        // throw the exception.
        for ($n = 0; $n < 99; $n++) {
            $battle->fightNRounds(1);
        }
    }
}
