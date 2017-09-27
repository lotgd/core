<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Game;
use LotGD\Core\Models\FighterInterface;

/**
 * Damage event where damage is the result of a life tap.
 */
class DamageLifetapEvent extends BattleEvent
{
    /** @var \LotGD\Core\Models\FighterInterface */
    protected $target;
    /** @var int */
    protected $healAmount;
    /** @var string */
    protected $message;

    /**
     * Construct a new DamageLifetapEvent where healing amount is $healAmount and
     * target is $target.
     * $message can contain '{target}' and '{damage}'
     * which will be replaced by the name of the target and the damage, respectively.
     * @param FighterInterface $target
     * @param int $healAmount
     * @param string $message
     */
    public function __construct(FighterInterface $target, int $healAmount, string $message)
    {
        $this->target = $target;
        $this->healAmount = $healAmount;
        $this->message = $message;
    }

    /**
     * Return the heal amount.
     * @return int
     */
    public function getHealAmount(): int
    {
        return $this->healAmount;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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
