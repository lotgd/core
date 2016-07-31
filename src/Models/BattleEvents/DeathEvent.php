<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Models\FighterInterface;

/**
 * BattleEvent representing a fighter's death.
 */
class DeathEvent extends BattleEvent
{
    protected $victim;

    /**
     * Construct a DeathEvent for victim $victim.
     * @param FighterInterface $victim
     */
    public function __construct(FighterInterface $victim)
    {
        $this->victim = $victim;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {

    }

    /**
     * @inheritDoc
     */
    public function decorate(Game $game): string
    {
        return "";
    }
}
