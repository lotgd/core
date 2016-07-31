<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;

/**
 * A representation of something that happened in battle.
 */
class BattleEvent
{
    private $applied = false;

    /**
     * Applies the event.
     * @throws BattleEventException
     */
    public function apply()
    {
        if ($this->applied === true) {
            throw new BattleEventException("Cannot apply an event more than once.");
        }

        $this->applied = true;
    }

    /**
     * Returns a string describing the event.
     * @param \LotGD\Core\Models\BattleEvents\Game $game
     * @return string
     * @throws BattleEventException
     */
    public function decorate(Game $game): string
    {
        if ($this->applied === false) {
            throw new BattleEventException("Buff needs to get applied before decoration.");
        }

        return "";
    }
}
