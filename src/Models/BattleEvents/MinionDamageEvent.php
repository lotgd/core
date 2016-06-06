<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;
use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent
 */
class MinionDamageEvent extends BattleEvent
{
    protected $target;
    protected $damage;
    protected $message;
    
    public function __construct(
        FighterInterface $target,
        int $damage,
        string $message
    ) {
        $this->target = $target;
        $this->damage = $damage;
        $this->message = $message;
    }
    
    public function decorate(Game $game): string
    {
        parent::decorate();
        
        return str_replace(
            [
                "{target}",
                "{amount}",
            ],
            [
                $this->target->getDisplayName(),
                $this->damage,
            ],
            $this->message
        );
    }
    
    public function apply()
    {
        parent::apply();
        $this->target->setHealth($this->target->getHealth() - $this->damage);
    }
}
