<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;
use LotGD\Core\Game;
use LotGD\Core\Models\FighterInterface;

/**
 * Battle event that represents regenerating health.
 */
class RegenerationBuffEvent extends BattleEvent
{
    protected $target;
    protected $regeneration;
    protected $effectMessage;
    protected $noEffectMessage;

    /**
     * Construct a RegenerationBuffEvent against $target, with regenerating value
     * $regeneration. $effectMessage is shown if there is an effect of
     * regeneration, and $noEffectMessage is shown if the $regeneation is 0.
     * $effectMessage and $noEffectMessage can contain '{target}' and '{amount}'
     * which will be replaced by the name of the target and the damage, respectively.
     * @param FighterInterface $target
     * @param int $regeneration
     * @param string $effectMessage
     * @param string $noEffectMessage
     */
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

    /**
     * @inheritDoc
     */
    public function decorate(Game $game): string
    {
        parent::decorate($game);

        if ($this->regeneration === 0) {
            return str_replace(
                "{target}",
                $target->getDisplayName(),
                $this->noEffectMessage
            );
        } else {
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

    /**
     * @inheritDoc
     */
    public function apply()
    {
        parent::apply();

        $healthLacking = $this->target->getMaxHealth() - $this->target->getHealth();
        $healthLeft = $this->target->getHealth();

        if ($this->regeneration > 0) {
            // Healing
            if ($healthLacking === 0) {
                $this->regeneration = 0;
            } elseif ($healthLacking < $this->regeneration) {
                $this->regeneration = $healthLacking;
            }
        } else {
            // Damaging
            if ($healthLeft === 0) {
                $this->regeneration = 0;
            } elseif ($healthLeft < -1*$this->regeneration) {
                $this->regeneration = - $healthLeft;
            }
        }

        $this->target->setHealth($this->target->getHealth() + $this->regeneration);
    }
}
