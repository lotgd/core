<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent
 */
class DamageEvent extends BattleEvent
{   
    /** @var FighterInstance */
    protected $attacker;
    /** @var FighterInstance */
    protected $defender;
    /** @var int Damage applied */
    protected $damage;
    
    public function __construct(FighterInterface $attacker, FighterInterface $defender, int $damage)
    {
        $this->attacker = $attacker;
        $this->defender = $defender;
        $this->damage = $damage;
    }
    
    /**
     * Returns the damage that is applied in this fight.
     * 
     * If the damage is > 0, the damage is applied to the defender. If it's < 0, it's applied to the attacker.
     * @return int
     */
    public function getDamage(): int
    {
        return $this->damage;
    }
    
    /**
     * Applies the damage.
     */
    public function apply()
    {
        parent::apply();
        
        if ($this->damage !== 0) {
            // Only damage the victim if there is an actual effect
            $victim = $this->damage > 0 ? $this->defender : $this->attacker;
            $victim->damage(abs($this->damage));
        }
    }
    
    /**
     * Returns a string describing the event.
     * @param \LotGD\Core\Models\BattleEvents\Game $game
     * @return string
     */
    public function decorate(Game $game): string
    {
        parent::decorate($game);
        
        $attackersName = $this->attacker->getDisplayName();
        $defendersName = $this->defender->getDisplayName();
            
        if ($this->damage === 0) {
            if ($this->attacker === $game->getCharacter()) {
                return "You try to hit {$defendersName} but MISS!";
            }
            else {
                return "{$attackersName} tries to hit you but they MISS!";
            }
        } elseif ($this->damage > 0) {
            if ($this->attacker === $game->getCharacter()) {
                return "You hit {$defendersName} for {$this->damage} points of damage!";
            }
            else {
                return "{$attackersName} hits you for {$this->damage} points of damage!";
            }
        } else {
            if ($this->attacker === $game->getCharacter()) {
                return "You try to hit {$defendersName} but are RIPOSTED for {$this->damage} points of damage";
            }
            else {
                return "{$attackersName} tries to hit you but you RIPOSTE for {$this->damage} points of damage";
            }
        }
    }
}
