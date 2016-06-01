<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;

/**
 * BattleEvent
 */
class BuffMessageEvent extends BattleEvent
{   
    private $message = "";
    
    public function __construct(string $message) {
        $this->message = $message;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function decorate(Game $game): string
    {
        return $message;
    }
}
