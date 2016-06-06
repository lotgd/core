<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent
 */
class DamageReflectionEvent extends BattleEvent
{   
    /** @var \LotGD\Core\Models\FighterInterface */
    protected $target;
    /** @var int */
    protected $damage;
    /** @var string */
    protected $message;
            
    public function __construct(FighterInterface $target, int $damage, string $message)
    {
        $this->target = $target;
        $this->damage = $damage;
        $this->message = $message;
    }
    
    /**
     * Returns the damage
     * @return int
     */
    public function getDamage(): int
    {
        return $this->damage;
    }
    
    /*
     * Applies the damage
     */
    public function apply()
    {
        parent::apply();
        
        if ($this->damage === 0) {
            return;
        } elseif ($this->damage > 0) {
            $this->target->setHealth($this->target->getHealth() - $this->damage);
        } else {
            $this->target->setHealth($this->target->getHealth() - $this->damage);
        }
    }
    
    /**
     * Returns a string describing the event
     * @param \LotGD\Core\Models\BattleEvents\Game $game
     * @return string
     */
    public function decorate(Game $game): string
    {
        parent::decorate($game);
        
        return str_replace(
            [
                "{target}", 
                "{damage}"
            ],
            [
                $this->target->getDisplayName(),
                $this->damage,
            ],
            $this->message
        );
    }
}
