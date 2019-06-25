<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Exceptions\BattleIsOverException;
use LotGD\Core\Exceptions\BattleNotOverException;
use LotGD\Core\Models\BattleEvents\CriticalHitEvent;
use LotGD\Core\Models\BattleEvents\DamageEvent;
use LotGD\Core\Models\BattleEvents\DeathEvent;
use LotGD\Core\Models\Buff;
use LotGD\Core\Models\FighterInterface;

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
    
    /**
     * Battle Configuration.
     * @var array
     */
    protected $configuration = [
        "riposteEnabled" => true,
        "levelAdjustementEnabled" => true,
        "criticalHitEnabled" => true,
    ];
    
    /**
     * Takes a game object and two participants (Player and Monster) to fight a battle.
     * @param Game $game
     * @param FighterInterface $player
     * @param FighterInterface $monster
     */
    public function __construct(Game $game, FighterInterface $player, ?FighterInterface $monster)
    {
        $this->game = $game;
        $this->player = $player;
        $this->monster = $monster;
        $this->events = new ArrayCollection();
    }

    /**
     * Returns a string which contains the important fields that must be serialized.
     * @return string
     */
    public function serialize(): string
    {
        return \serialize([
            "monster" => $this->monster,
            "result" => $this->result,
            "round" => $this->round,
            "configuration" => $this->configuration,
        ]);
    }

    /**
     * @param Game $game
     * @param FighterInterface $player
     * @param string $serialized
     * @return self
     */
    public static function unserialize(Game $game, FighterInterface $player, string $serialized): self
    {
        $battle = new self($game, $player, null);
        $unserialized = \unserialize($serialized);

        $battle->monster = $unserialized["monster"];
        $battle->result = $unserialized["result"];
        $battle->round = $unserialized["round"];
        $battle->configuration = $unserialized["configuration"];

        return $battle;
    }
    
    /**
     * Returns a list of all battle events.
     * @return Collection
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }
    
    /**
     * Disables ripostes.
     */
    public function disableRiposte()
    {
        $this->configuration["riposteEnabled"] = false;
    }
    
    /**
     * Enables ripostes.
     */
    public function enableRiposte()
    {
        $this->configuration["riposteEnabled"] = true;
    }
    
    /**
     * Returns true if ripostes are enabled.
     * @return bool
     */
    public function isRiposteEnabled(): bool
    {
        return $this->configuration["riposteEnabled"];
    }
    
    /**
     * Enables level adjustement.
     */
    public function enableLevelAdjustement()
    {
        $this->configuration["levelAdjustementEnabled"] = true;
    }
    
    /**
     * Disables level adjustement.
     */
    public function disableLevelAdjustement()
    {
        $this->configuration["levelAdjustementEnabled"] = false;
    }
    
    /**
     * Returns true if level adjustements are enabled.
     * @return bool
     */
    public function isLevelAdjustementEnabled(): bool
    {
        return $this->configuration["levelAdjustementEnabled"];
    }
    
    /**
     * Returns true if critical hit events are enabled.
     * @return bool
     */
    public function isCriticalHitEnabled(): bool
    {
        return $this->configuration["criticalHitEnabled"];
    }
    
    /**
     * Disable critical hits.
     */
    public function disableCriticalHit()
    {
        $this->configuration["criticalHitEnabled"] = false;
    }
    
    /**
     * enables critical hits.
     */
    public function enableCriticalHit()
    {
        $this->configuration["criticalHitEnabled"] = true;
    }
    
    /**
     * Returns true if the battle is over.
     * @return bool
     */
    public function isOver(): bool
    {
        return $this->result !== self::RESULT_UNDECIDED;
    }
    
    /**
     * Returns the player instance.
     * @return FighterInterface
     */
    public function getPlayer(): FighterInterface
    {
        return $this->player;
    }
    
    /**
     * Returns the montser instance.
     * @return FighterInterface
     */
    public function getMonster(): FighterInterface
    {
        return $this->monster;
    }
    
    /**
     * Returns the winner of this fight.
     * @throws BattleNotOverException if battle is not over.
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
     * Returns the loser of this fight.
     * @throws BattleNotOverException if battle is not over.
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
     * @param int $firstDamageRound Which damage rounds are calculated. Cannot be 0.
     * @throws ArgumentException if firstDamageRound is 0.
     * @throws BattleIsOverException
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
        
        return $count + 1;
    }
    
    /**
     * Fights exactly 1 round.
     * @param int $firstDamageRound
     */
    protected function fightOneRound(int $firstDamageRound)
    {
        $damageHasBeenDone = false;
        
        $this->player->getBuffs()->resetBuffUsage();
        $this->monster->getBuffs()->resetBuffUsage();
        
        $playerBuffStartEvents = $this->player->getBuffs()->activate(Buff::ACTIVATE_ROUNDSTART);
        $monsterBuffStartEvents = $this->monster->getBuffs()->activate(Buff::ACTIVATE_ROUNDSTART);
        
        $offenseTurnEvents = $firstDamageRound & self::DAMAGEROUND_PLAYER ? $this->turn($this->player, $this->monster) : new ArrayCollection();
        $defenseTurnEvents = $firstDamageRound & self::DAMAGEROUND_MONSTER ? $this->turn($this->monster, $this->player) : new ArrayCollection();

        $events = new ArrayCollection(\array_merge($offenseTurnEvents->toArray(), $defenseTurnEvents->toArray()));
        $eventsToAdd = new ArrayCollection();

        foreach ($events as $event) {
            $event->apply();

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

        $this->round++;
        
        $playerBuffEndEvents = $this->player->getBuffs()->activate(Buff::ACTIVATE_ROUNDEND);
        $monsterBuffEndEvents = $this->monster->getBuffs()->activate(Buff::ACTIVATE_ROUNDEND);
        
        $playerBuffExpiringEvents = $this->player->getBuffs()->expireOneRound();
        $monsterBuffExpiringEvents = $this->monster->getBuffs()->expireOneRound();
        
        $this->events = new ArrayCollection(
            \array_merge(
                $this->events->toArray(),
                $playerBuffStartEvents->toArray(),
                $monsterBuffStartEvents->toArray(),
                $eventsToAdd->toArray(),
                $playerBuffEndEvents->toArray(),
                $monsterBuffEndEvents->toArray(),
                $playerBuffExpiringEvents->toArray(),
                $monsterBuffExpiringEvents->toArray(),
                isset($deathEvent) ? [$deathEvent] : []
            )
        );
    }
    
    /**
     * Runs one turn.
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @return ArrayCollection
     */
    protected function turn(FighterInterface $attacker, FighterInterface $defender): ArrayCollection
    {
        $events = new ArrayCollection();
        
        $attackersBuffs = $attacker->getBuffs();
        $defendersBuffs = $defender->getBuffs();
        
        // Adjustement makes fights versus monsters with lower level easier,
        // and more difficult if the monster has a higher level by adjusting
        // the monster's defense value.
        // For example, if a level 10 player attacks a level 9 monster, the
        // defenseAdjustement value for the monster is 0.81, reducing the monster's
        // defense by 20% and making it more likely for the player to land a hit.
        // On the other hand, the player's defense is increased by ~ 10%, making it
        // less likely for the enemy to hit the player.
        $adjustement = 1.0;
        $defenseAdjustement = 1.0;
        if ($attacker === $this->player && $this->isLevelAdjustementEnabled()) {
            if ($attacker->getLevel() > 1 && $defender->getLevel() > 1) {
                $adjustement = $attacker->getLevel() / $defender->getLevel();
                $defenseAdjustement = 1. / ($adjustement * $adjustement);
            }
        } elseif ($defender === $this->player && $this->isLevelAdjustementEnabled()) {
            if ($attacker->getLevel() > 1 && $defender->getLevel() > 1) {
                $adjustement = $defender->getLevel() / $attacker->getLevel();
                $defenseAdjustement = $adjustement;
            }
        }
        
        // Apply buff scaling for the attacker's attack - this needs to take into
        // account the attacker's goodguyAttackModifier and the defenders badguyAttackModifier
        $attackersAttack = $attacker->getAttack()
            * $attackersBuffs->getGoodguyAttackModifier()
            * $defendersBuffs->getBadguyAttackModifier();
        // It's the opposite for the defender's defense - it needs to take into account the
        // defender's goodguyDefenseModifier as well as the attacker's badguyDefenseModifier.
        $defendersDefense = $defender->getDefense()
            * $defendersBuffs->getGoodguyDefenseModifier()
            * $attackersBuffs->getBadguyDefenseModifier()
            * $defenseAdjustement;
        
        // If the player is the attacker, we enable critical hits with a chance of 25%.
        if ($attacker === $this->game->getCharacter()) {
            // Players can land critical hits
            if ($this->game->getDiceBag()->chance(0.25) && $this->isCriticalHitEnabled()) {
                $attackersAttack *= 3;
            }
        }
        
        // Conversion from float to int, since the random number generator takes int values.
        $attackersAttack = (int)\round($attackersAttack, 0);
        $defendersDefense = (int)\round($defendersDefense, 0);
        
        // Lets roll the
        $attackersAtkRoll = $this->game->getDiceBag()->pseudoBell(0, $attackersAttack);
        $defendersDefRoll = $this->game->getDiceBag()->pseudoBell(0, $defendersDefense);
        $damage = $attackersAtkRoll - $defendersDefRoll;
        
        // If the attacker's attack after modification is bigger than before,
        // we call it a critical hit and apply the CriticalHitEvent.
        if ($attackersAttack > $attacker->getAttack() && $this->isCriticalHitEnabled()) {
            $events->add(new CriticalHitEvent($attacker, $attackersAttack));
        }
        
        // Set damage to 0 if riposte has been disabled
        if ($this->isRiposteEnabled() === false && $damage < 0) {
            $damage = 0;
        }
        
        // Here, we take invulnurable buffs into account. There are 4 possible values coming from the
        // 2 buff lists, so we must take care a bit.
        $attackerIsInvulnurable = $attackersBuffs->goodguyIsInvulnurable() || $defendersBuffs->badguyIsInvulnurable();
        $defenderIsInvulnurable = $defendersBuffs->goodguyIsInvulnurable() || $attackersBuffs->badguyIsInvulnurable();
        
        if ($attackerIsInvulnurable && $defenderIsInvulnurable) {
            // Both are invulnurable, damage is 0.
            $damage = 0;
        } elseif ($attackerIsInvulnurable) {
            // Attaker is invulnurable, damage is always > 0 (there is no riposte)
            $damage = \abs($damage);
        } elseif ($defenderIsInvulnurable) {
            // Defender is invulnurable, damage is always < 0 (defender always ripostes)
            $damage = -\abs($damage);
        }
        
        if ($damage < 0) {
            // If the damage is less then 0, it's a RIPOSTE. They are only half
            // as damaging than normal attacks.
            $damage /= 2;
            
            // Apply damage modification. It's a RIPOSTE, so the defenders makes the
            // damage. Therefore, we take defender's goodguyDamageModifier into account,
            // and the attacker's badguyDamageModifier.
            $damage *= $defendersBuffs->getGoodguyDamageModifier()
                * $attackersBuffs->getBadguyDamageModifier();
        } else {
            // Apply damage modification. It's a normal attack - meaning the attacker does
            // the damage. Therefore, we take the attacker's goodguyDamageModifier and
            // the defender's badguyDamageModifier into account.
            $damage *= $attackersBuffs->getGoodguyDamageModifier()
                * $defendersBuffs->getBadguyDamageModifier();
        }
        
        // Round the damage value and convert to int.
        $damage = (int)\round($damage, 0);
        
        // Add the damage event
        $events->add(new DamageEvent($attacker, $defender, $damage));
        
        // Do all the other buff effects. Modifiers are calculated separatly and do not need activation
        $attackersBuffStartEvents = $attackersBuffs->activate(Buff::ACTIVATE_OFFENSE);
        $defendersBuffStartEvents = $defendersBuffs->activate(Buff::ACTIVATE_DEFENSE);
        
        $attackersDirectBuffEvents = $attackersBuffs->processDirectBuffs(Buff::ACTIVATE_OFFENSE, $this->game, $attacker, $defender);
        $defendersDirectBuffEvents = $defendersBuffs->processDirectBuffs(Buff::ACTIVATE_DEFENSE, $this->game, $defender, $attacker);
        
        $attackersDamageDependentBuffEvents = $attackersBuffs->processDamageDependentBuffs(Buff::ACTIVATE_OFFENSE, $damage, $this->game, $attacker, $defender);
        $defendersDamageDependentBuffEvents = $defendersBuffs->processDamageDependentBuffs(Buff::ACTIVATE_DEFENSE, -$damage, $this->game, $defender, $attacker);
        
        return new ArrayCollection(
            \array_merge(
                $attackersBuffStartEvents->toArray(),
                $attackersDirectBuffEvents->toArray(),
                $defendersBuffStartEvents->toArray(),
                $defendersDirectBuffEvents->toArray(),
                $events->toArray(),
                $attackersDamageDependentBuffEvents->toArray(),
                $defendersDamageDependentBuffEvents->toArray()
            )
        );
    }
}
