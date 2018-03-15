<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Game;
use LotGD\Core\Models\FighterInterface;

/**
 * Battle event representing a stronger than average attack.
 */
class CriticalHitEvent extends BattleEvent
{
    /** @var FighterInstance */
    protected $attacker;
    /** @var int */
    protected $criticalAttackValue;

    /**
     * Construct a CriticalHitEvent with attacker $attacker.
     * @param FighterInterface $attacker
     * @param int $criticalAttackValue
     */
    public function __construct(FighterInterface $attacker, int $criticalAttackValue)
    {
        $this->attacker = $attacker;
        $this->criticalAttackValue = $criticalAttackValue;
    }

    /**
     * @inheritDoc
     */
    public function decorate(Game $game): string
    {
        $pureAttackersAttack = $this->attacker->getAttack(true);

        if ($this->criticalAttackValue > $pureAttackersAttack * 4) {
            return "You execute a MEGA power move!!!";
        } elseif ($this->criticalAttackValue > $pureAttackersAttack * 3) {
            return "You execute a DOUBLE power move!!!";
        } elseif ($this->criticalAttackValue > $pureAttackersAttack * 2) {
            return "You execute a power move!!!";
        } elseif ($this->criticalAttackValue > $pureAttackersAttack * 1.25) {
            return "You execute a minor power move!";
        }
    }
}
