<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use Doctrine\Common\Collections\Collection;

use LotGD\Core\{
    Battle,
    DiceBag,
    Game,
    Models\Buff,
    Models\Character,
    Models\Monster
};
use LotGD\Core\Models\BattleEvents\{
    BuffMessageEvent
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
     * Tests a fight which the player has to lose (lvl 1 vs lvl 100)
     */
    public function testPlayerLoseBattle()
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
    public function testBattleNotOverExceptionFromLoser()
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
        
        // Fighting for 99 rounds should be enough for determining a loser - and to
        // throw the exception.
        for ($n = 0; $n < 99; $n++) {
            $battle->fightNRounds(1);
        }
    }
    
    private function provideBuffBattleParticipants(Buff $buff, int $participantsType): Battle
    {
        $em = $this->getEntityManager();
        $em->clear();
        
        switch ($participantsType) {
            default:
            case 0:
                // Fair Battle
                $character = $em->getRepository(Character::class)->find(1);
                $monster = $em->getRepository(Monster::class)->find(1);
                break;
            case 1:
                // very long battle
                $character = $em->getRepository(Character::class)->find(4);
                $monster = $em->getRepository(Monster::class)->find(3);
                break;
            case 2:
                // player should win battle
                $character = $em->getRepository(Character::class)->find(13);
                $monster = $em->getRepository(Monster::class)->find(11);
                break;
            case 3:
                // player should lose battle
                $character = $em->getRepository(Character::class)->find(11);
                $monster = $em->getRepository(Monster::class)->find(13);
                break;
        }
        
        $character->addBuff($buff);
        
        return new Battle($this->getMockGame($character), $character, $monster);
    }
    
    /**
     * Asserts that a certain BuffMessageEvent with a specific text is contained in the lst of events
     * @param Collection $events The list of events
     * @param string $battleEventText The text to test for
     * @param int $timesAtLeast Mininum number of times the message is expected to be in the event list
     * @param int? $timesAtMax Maximum number of times the message is expected to be in the event list, or $timesAtLeast if null.
     */
    protected function assertBuffEventMessageExists(
        Collection $events, 
        string $battleEventText, 
        int $timesAtLeast = 1, 
        int $timesAtMax = null
    ) {
        $eventCounter = 0;
        foreach($events as $event) {
            if ($event instanceof BuffMessageEvent) {
                if ($battleEventText === $event->getMessage()) {
                    $eventCounter++;
                }
            }
        }
        
        if ($timesAtMax === null) {
            $timesAtMax = $timesAtLeast;
        }
        
        $this->assertGreaterThanOrEqual($timesAtLeast, $eventCounter);
        $this->assertLessThanOrEqual($timesAtMax, $eventCounter);
    }
    
    /**
     * Tests normal buff messages - message upon start of the buff, message every 
     * round (except when it's started), and the message displayed if the buff expires.
     */
    public function testBattleBuffMessages()
    {
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 3,
            "startMessage" => "And this buff starts!",
            "roundMessage" => "The buff is still activate",
            "endMessage" => "The buff is ending.",
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 1);
        
        // We fight for 5 rounds - this ensures that the buff is started and expired.
        $battle->fightNRounds(5);
        
        $this->assertBuffEventMessageExists($battle->getEvents(), "And this buff starts!", 1);
        $this->assertBuffEventMessageExists($battle->getEvents(), "The buff is ending.", 1);
        $this->assertBuffEventMessageExists($battle->getEvents(), "The buff is still activate", 1, 2);
        
        $expectedEvents = [
            BuffMessageEvent::class, // Activation round
            \LotGD\Core\Models\BattleEvents\DamageEvent::class, // Round 1
            \LotGD\Core\Models\BattleEvents\DamageEvent::class,
            BuffMessageEvent::class, // message every round
            \LotGD\Core\Models\BattleEvents\DamageEvent::class, // Round 2
            \LotGD\Core\Models\BattleEvents\DamageEvent::class,
            BuffMessageEvent::class, // message every round
            \LotGD\Core\Models\BattleEvents\DamageEvent::class, // Round 3
            \LotGD\Core\Models\BattleEvents\DamageEvent::class,
            BuffMessageEvent::class, // message expires
            \LotGD\Core\Models\BattleEvents\DamageEvent::class, // Round 4
            \LotGD\Core\Models\BattleEvents\DamageEvent::class,
            \LotGD\Core\Models\BattleEvents\DamageEvent::class, // Round 5
            \LotGD\Core\Models\BattleEvents\DamageEvent::class,
        ];
        
        $numOfEvents = count($battle->getEvents());
        for ($i = 0; $i < $numOfEvents; $i++) {
            $this->assertInstanceOf($expectedEvents[$i], $battle->getEvents()[$i]);
        }
    }
            
    public function testBattleBuffPlayerGoodguyModifier()
    {
        // Get a battle ready
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "goodguyAttackModifier" => 0.0,
            "goodguyDefenseModifier" => 0.0,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 2);
        
        $rounds = $battle->fightNRounds(99);
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getPlayer(), $battle->getLoser());
        
        // Get a battle that the player should lose and apply a buff that the player forces to win
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "goodguyAttackModifier" => 2,
            "goodguyDefenseModifier" => 2,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 3);
        
        $battle->fightNRounds(99);
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getPlayer(), $battle->getWinner());
    }
    
    public function testBattleBuffPlayerBadguyModifier()
    {
        // Get a battle that the player should win and apply a buff that the player forces to lose.
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "badguyAttackModifier" => 10,
            "badguyDefenseModifier" => 10,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 2);
        
        $rounds = $battle->fightNRounds(99);
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getPlayer(), $battle->getLoser());
        
        // Get a battle that the player should lose and apply a buff that the player forces to win
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "badguyAttackModifier" => 0,
            "badguyDefenseModifier" => 0,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 3);
        
        $battle->fightNRounds(99);
        
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getPlayer(), $battle->getWinner());
    }
    
    public function testBattleBuffPlayerDamageModifier()
    {
        // Get a battle that the player should win and apply a buff that the player forces to lose
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "goodguyDamageModifier" => 0.0,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 0);
        
        $rounds = $battle->fightNRounds(10);
        
        $this->assertSame($battle->getMonster()->getMaxHealth(), $battle->getMonster()->getHealth());
        
        // Get a battle that the player should lose and apply a buff that the player forces to win
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "badguyDamageModifier" => 0.0,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 0);
        
        $battle->fightNRounds(10);
        
        $this->assertSame($battle->getPlayer()->getMaxHealth(), $battle->getPlayer()->getHealth());
    }
    
    public function testBattleBuffPlayerInvulnurability()
    {
        // Get a battle that the player should win and apply a buff that the player forces to lose
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "badguyInvulnurable" => true,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 0);
        
        $rounds = $battle->fightNRounds(99);
        
        $this->assertSame($battle->getMonster()->getMaxHealth(), $battle->getMonster()->getHealth());
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getMonster(), $battle->getWinner());
        
        // Get a battle that the player should lose and apply a buff that the player forces to win
        $battle = $this->provideBuffBattleParticipants(new Buff([
            "slot" => "test",
            "rounds" => 99,
            "goodguyInvulnurable" => true,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]), 0);
        
        $rounds = $battle->fightNRounds(99);
        
        $this->assertSame($battle->getPlayer()->getMaxHealth(), $battle->getPlayer()->getHealth());
        $this->assertTrue($battle->isOver());
        $this->assertSame($battle->getPlayer(), $battle->getWinner());
    }
    
    public function testBufflistGoodguyAttackModifier()
    {
        $em = $this->getEntityManager();
        $player = $em->getRepository(Character::class)->find(1);
        $game = $this->getMockGame($player);
        
        $player->addBuff(new Buff([
            "slot" => "test1",
            "rounds" => 1,
            "goodguyAttackModifier" => 1.23,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test2",
            "rounds" => 1,
            "goodguyAttackModifier" => 0.126,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test3",
            "rounds" => 1,
            "goodguyAttackModifier" => 13.4,
        ]));

        $modifier = $player->getBuffs()->getGoodguyAttackModifier();
        $this->assertEquals(0.15498, $modifier, '', 0.001);
    }
    
    public function testBufflistGoodguyDefenseModifier()
    {
        $em = $this->getEntityManager();
        $player = $em->getRepository(Character::class)->find(1);
        $game = $this->getMockGame($player);
        
        $player->addBuff(new Buff([
            "slot" => "test1",
            "rounds" => 1,
            "goodguyDefenseModifier" => 1.293,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test2",
            "rounds" => 1,
            "goodguyDefenseModifier" => 5.6,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test3",
            "rounds" => 1,
            "goodguyDefenseModifier" => 0,
        ]));

        $modifier = $player->getBuffs()->getGoodguyDefenseModifier();
        $this->assertEquals(7.2408, $modifier, '', 0.001);
    }
    
    public function testBufflistGoodguyDamageModifier()
    {
        $em = $this->getEntityManager();
        $player = $em->getRepository(Character::class)->find(1);
        $game = $this->getMockGame($player);
        
        $player->addBuff(new Buff([
            "slot" => "test1",
            "rounds" => 1,
            "goodguyDamageModifier" => 10,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test2",
            "rounds" => 1,
            "goodguyDamageModifier" => 0.25,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test3",
            "rounds" => 1,
            "goodguyDamageModifier" => 3.5,
        ]));

        $modifier = $player->getBuffs()->getGoodguyDamageModifier();
        $this->assertEquals(2.5, $modifier, '', 0.001);
    }
    
    public function testBufflistBadguyAttackModifier()
    {
        $em = $this->getEntityManager();
        $player = $em->getRepository(Character::class)->find(1);
        $game = $this->getMockGame($player);
        
        $player->addBuff(new Buff([
            "slot" => "test1",
            "rounds" => 1,
            "badguyAttackModifier" => 1.23,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test2",
            "rounds" => 1,
            "badguyAttackModifier" => 0.126,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test3",
            "rounds" => 1,
            "badguyAttackModifier" => 13.4,
        ]));

        $modifier = $player->getBuffs()->getBadguyAttackModifier();
        $this->assertEquals(0.15498, $modifier, '', 0.001);
    }
    
    public function testBufflistBadguyDefenseModifier()
    {
        $em = $this->getEntityManager();
        $player = $em->getRepository(Character::class)->find(1);
        $game = $this->getMockGame($player);
        
        $player->addBuff(new Buff([
            "slot" => "test1",
            "rounds" => 1,
            "badguyDefenseModifier" => 1.293,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test2",
            "rounds" => 1,
            "badguyDefenseModifier" => 5.6,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test3",
            "rounds" => 1,
            "badguyDefenseModifier" => 0,
        ]));

        $modifier = $player->getBuffs()->getBadguyDefenseModifier();
        $this->assertEquals(7.2408, $modifier, '', 0.001);
    }
    
    public function testBufflistBadguyDamageModifier()
    {
        $em = $this->getEntityManager();
        $player = $em->getRepository(Character::class)->find(1);
        $game = $this->getMockGame($player);
        
        $player->addBuff(new Buff([
            "slot" => "test1",
            "rounds" => 1,
            "badguyDamageModifier" => 10,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test2",
            "rounds" => 1,
            "badguyDamageModifier" => 0.25,
            "activateAt" => Buff::ACTIVATE_ROUNDSTART,
        ]));
        $player->addBuff(new Buff([
            "slot" => "test3",
            "rounds" => 1,
            "badguyDamageModifier" => 3.5,
        ]));

        $modifier = $player->getBuffs()->getBadguyDamageModifier();
        $this->assertEquals(2.5, $modifier, '', 0.001);
    }
}
