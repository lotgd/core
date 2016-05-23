<?php
declare(strict_types=1);

namespace LotGD\Core;

use LotGD\Core\{
    DiceBag,
    Exceptions\ArgumentException,
    Exceptions\BattleIsOverException,
    Exceptions\BattleNotOverException,
    Models\FighterInterface
};

/**
 * Description of Battle
 */
class Battle
{
    const DAMAGEROUND_PLAYER = 0b01;
    const DAMAGEROUND_MONSTER = 0b10;
    const DAMAGEROUND_BOTH = 0b11;
    
    protected $player;
    protected $monster;
    protected $diceBag;
    protected $isOver = false;
    protected $winner;
    protected $looser;
    
    public function __construct(FighterInterface $player, FighterInterface $monster)
    {
        $this->player = $player;
        $this->monster = $monster;
        $this->diceBag = new DiceBag();
    }
    
    public function getActions()
    {
        
    }
    
    public function selectAction()
    {
        
    }
    
    /**
     * Returns true if the battle is over.
     * @return type
     */
    public function isOver()
    {
        return $this->isOver;
    }
    
    /**
     * Returns the winner of this fight
     * @return FighterInterface
     */
    public function getWinner(): FighterInterface
    {
        if (is_null($this->winner)) {
            throw new BattleNotOverException('There is no winner yet.');
        }
        return $this->winner;
    }
    
    /**
     * Returns the looser of this fight
     * @return FighterInterface
     */
    public function getLooser(): FighterInterface
    {
        if (is_null($this->looser)) {
            throw new BattleNotOverException('There is no looser yet.');
        }
        return $this->looser;
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
        
        if ($this->isOver === true) {
            throw new BattleIsOverException('This battle has already ended. You cannot fight anymore rounds.');
        }
        
        for ($count = 0; $count < $n; $count++) {
            $this->fightOneRound($firstDamageRound);
            $isSurprised = self::DAMAGEROUND_BOTH;
            
            // If one of the participants is dead, abort.
            if ($this->player->isAlive() === false || $this->monster->isAlive() === false) {
                $this->isOver = true;
                
                $this->winner = $this->player->isAlive() ? $this->player : $this->monster;
                $this->looser = $this->player->isAlive() ? $this->monster : $this->player;
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
        // playerDamage is the damage done to the player, to the monster.
        list($playerDamage, $monsterDamage, $playerAttack) = $this->calculateDamage();
        
        // Player does damage to the monster
        if ($firstDamageRound & self::DAMAGEROUND_PLAYER
            && $this->player->isAlive()
            && $this->monster->isAlive()
        ) {
            if ($monsterDamage < 0) {
                // The damage done to the monster is negative.
                // This means that the monster conters the player's attack
                $this->player->damage(0 - $monsterDamage);
            } elseif ($monsterDamage > 0) {
                // The damage done to the monster is positive.
                // This means that this is a normal attack
                $this->monster->damage($monsterDamage);
            } else {
                // The damage done to the monster is 0.
                // We interpretate this as a miss.
            }
        }
        
        // Monster does damage to the player
        if ($firstDamageRound & self::DAMAGEROUND_MONSTER
            && $this->player->isAlive()
            && $this->monster->isAlive()
        ) {
            if ($playerDamage > 0) {
                // The damage done to the player is negative
                // THis means that the player conters the monster's attack
                $this->monster->damage(0 - $playerDamage);
            } elseif($playerDamage > 0) {
                // The damage done to the player is positive.
                // This means that this is a normal attack
                $this->player->damage($playerDamage);
            }
            else {
                // The damage done to the player is 0.
                // We interpretate this as a miss.
            }
        }
    }
    
    /**
     * Returns the damage done to the player and to the monster.
     * @return array [playerDamage, monsterDamage, playerAttack]
     */
    protected function calculateDamage(): array
    {
        $monsterDefense = $this->monster->getDefense();
        $monsterAttack = $this->monster->getAttack();
        $playerDefense = $this->player->getDefense();
        $playerAttack = $this->player->getAttack();
        
        $monsterDamage = 0;
        $playerDamage = 0;
        
        while ($monsterDamage === 0 && $playerDamage === 0) {
            $atk = $playerAttack;
            
            // Critical hit probablity is derived from the old e_rand() function.
            // e_rand(1, 3) == 3 has a probablity of ~25%.
            if ($this->diceBag->chance(0.25)) {
                $atk *= 3;
            }
            
            // Calculate damage done to the monster
            $playerAtkRoll = $this->diceBag->normal(0, $atk);
            $monsterDefRoll = $this->diceBag->normal(0, $monsterDefense);
            $monsterDamage = $playerAtkRoll - $monsterDefRoll;
            
            if ($monsterDamage < 0) {
                $monsterDamage /= 2;
            }
            
            // Calculate damage done to the player
            $playerDefRoll = $this->diceBag->normal(0, $playerDefense);
            $monsterAtkRoll = $this->diceBag->normal(0, $monsterAttack);
            $playerDamage = $monsterAtkRoll - $playerDefRoll;
            
            if ($playerDamage < 0) {
                $playerDamage /= 2;
            }
        }
        
        return [
            (int)round($playerDamage, 0),
            (int)round($monsterDamage, 0),
            $atk
        ];
    }
}
