<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;

/**
 * BattleEvent
 */
class BattleEvent
{   
    private $applied = false;
    
    public function apply()
    {
        if ($this->applied === true) {
            throw new BattleEventException("Cannot apply an event more than once.");
        }
        
        $this->applied = true;
    }
    
    public function decorate(Game $game): string
    {
        return "";
    }
}
