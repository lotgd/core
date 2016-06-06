<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;
use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent
 */
class RegenerationBuffEvent extends BattleEvent
{
    protected $target;
    protected $regeneration;
    protected $effectMessage;
    protected $noEffectMessage;
    
    public function __construct(
        FighterInterface $target,
        int $regeneration,
        string $effectMessage,
        string $noEffectMessage
    ) {
        $this->target = $target;
        $this->regeneration = $regeneration;
        $this->effectMessage = $effectMessage;
        $this->noEffectMessage = $noEffectMessage;
    }
    
    public function decorate(Game $game): string
    {
        parent::decorate();
        
        if ($this->regeneration === 0) {
            return str_replace(
                "{target}",
                $target->getDisplayName(),
                $this->noEffectMessage
            );
        }
        else {
            return str_replace(
                [
                    "{target}",
                    "{amount}"
                ],
                [
                    $target->getDisplayName(),
                    $this->regeneration,
                ],
                $this->effectMessage
            );
        }
    }
    
    public function apply()
    {
        parent::apply();
        
        $healthLacking = $this->target->getMaxHealth() - $this->target->getHealth();
        $healthLeft = $this->target->getHealth();
        
        if ($this->regeneration > 0) {
            // Healing
            if ($healthLacking === 0) {
                $this->regeneration = 0;
            }
            elseif ($healthLacking < $this->regeneration) {
                $this->regeneration = $healthLacking;
            }
        }
        else {
            // Damaging
            if ($healthLeft === 0) {
                $this->regeneration = 0;
            }
            elseif ($healthLeft < -1*$this->regeneration) {
                $this->regeneration = - $healthLeft;
            }
        }
        
        $this->target->setHealth($this->target->getHealth() + $this->regeneration);
    }
}
