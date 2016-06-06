<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent
 */
class DamageLifetapEvent extends BattleEvent
{   
    /** @var \LotGD\Core\Models\FighterInterface */
    protected $target;
    /** @var int */
    protected $healAmount;
    /** @var string */
    protected $message;
            
    public function __construct(FighterInterface $target, int $healAmount, string $message)
    {
        $this->target = $target;
        $this->healAmount = $healAmount;
        $this->message = $message;
    }
    
    public function getHealAmount(): int
    {
        return $this->healAmount;
    }
    
    public function apply()
    {
        parent::apply();
        
        if ($this->healAmount === 0) {
            return;
        } elseif ($this->healAmount > 0) {
            $this->target->setHealth($this->target->getHealth() + $this->healAmount);
        } else {
            $this->target->setHealth($this->target->getHealth() + $this->healAmount);
        }
    }
    
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
                $this->healAmount,
            ],
            $this->message
        );
    }
}
