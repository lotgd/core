<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Collections\ArrayCollection;

use LotGD\Core\{
    DiceBag,
    Exceptions\ArgumentException,
    Exceptions\BattleIsOverException,
    Exceptions\BattleNotOverException,
    Models\FighterInterface
};
use LotGD\Core\Models\BattleEvents\{
    BuffMessageEvent,
    CriticalHitEvent,
    DamageEvent,
    DeathEvent
};

/**
 * Class for managing and running battles between 2 participants.
 * Original damage calculation is from LotGD 0.9.7+jt by Eric Stevens and JTraub,
 * released originally under GPL 2.0.
 */
class Battle
{
    const DAMAGEROUND_PLAYER = 0b01;
    const DAMAGEROUND_MONSTER = 0b10;
    const DAMAGEROUND_BOTH = 0b11;
    
    const RESULT_UNDECIDED = 0;
    const RESULT_PLAYERDEATH = 1;
    const RESULT_MONSTERDEATH = 2;
    
    protected $player;
    protected $monster;
    protected $game;
    protected $events;
    protected $result = 0;
    protected $round = 0;
    
    public function __construct(Game $game, FighterInterface $player, FighterInterface $monster)
    {
        $this->game = $game;
        $this->player = $player;
        $this->monster = $monster;
        $this->events = new ArrayCollection();
    }
    
    public function getActions()
    {
        
    }
    
    public function selectAction()
    {
        
    }
    
    public function getEvents()
    {
        return $this->events;
    }
    
    /**
     * Returns true if the battle is over.
     * @return type
     */
    public function isOver()
    {
        return $this->result !== self::RESULT_UNDECIDED;
    }
    
    /**
     * Returns the winner of this fight
     * @return FighterInterface
     */
    public function getWinner(): FighterInterface
    {
        if ($this->isOver() === false) {
            throw new BattleNotOverException('There is no winner yet.');
        }
        
        return $this->result === self::RESULT_PLAYERDEATH ? $this->monster : $this->player;
    }
    
    /**
     * Returns the looser of this fight
     * @return FighterInterface
     */
    public function getLoser(): FighterInterface
    {
        if ($this->isOver() === false) {
            throw new BattleNotOverException('There is no winner yet.');
        }
        
        return $this->result === self::RESULT_PLAYERDEATH ? $this->player : $this->monster;
    }
    
    /**
     * Fights the number of rounds given by the parameter $n and returns the number
     * of actual rounds fought.
     * @param int $n
     * @param bool $firstDamageRound Which damage rounds are calculated. Cannot be 0.
     * @return int Number of fights fought.
     */
    public function fightNRounds(int $n = 1, int $firstDamageRound = self::DAMAGEROUND_BOTH): int
    {
        if ($firstDamageRound === 0) {
            throw new ArgumentException('$firstDamageRound must not be 0.');
        }
        
        if ($this->isOver()) {
            throw new BattleIsOverException('This battle has already ended. You cannot fight anymore rounds.');
        }
        
        for ($count = 0; $count < $n; $count++) {
            $this->fightOneRound($firstDamageRound);
            $firstDamageRound = self::DAMAGEROUND_BOTH;
            
            if ($this->isOver()) {
                break;
            }
        }
        
        return $count;
    }
    
    /**
     * Fights exactly 1 round
     * @param int $firstDamageRound
     */
    protected function fightOneRound(int $firstDamageRound)
    {
        $damageHasBeenDone = false;
        
        $playerBuffStartEvents = $this->player->getBuffs()->activate();
        $monsterBuffStartEvents = $this->monster->getBuffs()->activate();
        
        do {
            $offenseTurnEvents = $firstDamageRound & self::DAMAGEROUND_PLAYER ? $this->turn($this->player, $this->monster) : new ArrayCollection();
            $defenseTurnEvents = $firstDamageRound & self::DAMAGEROUND_MONSTER ? $this->turn($this->monster, $this->player) : new ArrayCollection();

            $events = new ArrayCollection(array_merge($offenseTurnEvents->toArray(), $defenseTurnEvents->toArray()));
            $eventsToAdd = new ArrayCollection();

            foreach($events as $event) {
                $event->apply();
                
                if ($event instanceof DamageEvent && $event->getDamage() !== 0) {
                    $damageHasBeenDone = true;
                }

                $eventsToAdd->add($event);

                if ($this->player->getHealth() <= 0) {
                    $deathEvent = new DeathEvent($this->player);
                    $this->result = self::RESULT_PLAYERDEATH;
                    break;
                }

                if ($this->monster->getHealth() <= 0) {
                    $deathEvent = new DeathEvent($this->monster);
                    $this->result = self::RESULT_MONSTERDEATH;
                    break;
                }
            }
        } while($damageHasBeenDone === false);
        
        $this->round++;
        
        $playerBuffEndEvents = $this->player->getBuffs()->expireOneRound();
        $monsterBuffEndEvents = $this->monster->getBuffs()->expireOneRound();
        
        $this->events = new ArrayCollection(
            array_merge(
                $this->events->toArray(), 
                $playerBuffStartEvents->toArray(),
                $monsterBuffStartEvents->toArray(),
                $eventsToAdd->toArray(),
                $playerBuffEndEvents->toArray(),
                $monsterBuffEndEvents->toArray(),
                isset($deathEvent) ? [$deathEvent] : []
            )
        );
    }
    
    /**
     * Runs one turn.
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     */
    protected function turn(FighterInterface $attacker, FighterInterface $defender): ArrayCollection
    {
        $events = new ArrayCollection();
        
        $attackersBuffs = $attacker->getBuffs();
        $defendersBuffs = $defender->getBuffs();
        
        $attackersAttack = $attacker->getAttack($this->game);
        $defendersDefense = $defender->getDefense($this->game);
        
        if ($attacker === $this->game->getCharacter()) {
            // Players can land critical hits
            if ($this->game->getDiceBag()->chance(0.25)) {
                $attackersAttack *= 3;
            }
        }
        
        $attackersAtkRoll = $this->game->getDiceBag()->normal(0, $attackersAttack);
        $defendersDefRoll = $this->game->getDiceBag()->normal(0, $defendersDefense);
        $damage = $attackersAtkRoll - $defendersDefRoll;
        
        if ($attackersAttack > $attacker->getAttack($this->game, true)) {
            $events->add(new CriticalHitEvent($attacker, $attackersAttack));
        }
        
        if ($damage < 0) {
            // RIPOSTE are only half as damaging than normal attacks
            $damage /= 2;
        }
        
        $damage = (int)round($damage, 0);
        
        $events->add(new DamageEvent($attacker, $defender, $damage));
        
        return $events;
    }
}
