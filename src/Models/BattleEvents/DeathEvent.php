<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent
 */
class DeathEvent extends BattleEvent
{   
    protected $victim;
    
    public function __construct(FighterInterface $victim)
    {
        $this->victim = $victim;
    }
    
    public function apply()
    {
        
    }
    
    public function decorate(Game $game): string
    {
        return "";
    }
}
