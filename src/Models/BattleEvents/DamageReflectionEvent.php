<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Game;
use LotGD\Core\Models\FighterInterface;

/**
 * A battle event representing damage being reflected back on the attacker.
 */
class DamageReflectionEvent extends BattleEvent
{
    /** @var \LotGD\Core\Models\FighterInterface */
    protected $target;
    /** @var int */
    protected $damage;
    /** @var string */
    protected $message;

    /**
     * Construct a DamageReflectionEvent with the target $target, damage amount
     * $damage and the message $message.
     * $message can contain '{target}' and '{damage}'
     * which will be replaced by the name of the target and the damage, respectively.
     * @param FighterInterface $target
     * @param int $damage
     * @param string $message
     */
    public function __construct(FighterInterface $target, int $damage, string $message)
    {
        $this->target = $target;
        $this->damage = $damage;
        $this->message = $message;
    }

    /**
     * Returns the damage
     * @return int
     */
    public function getDamage(): int
    {
        return $this->damage;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        parent::apply();

        if ($this->damage === 0) {
            return;
        } elseif ($this->damage > 0) {
            $this->target->setHealth($this->target->getHealth() - $this->damage);
        } else {
            $this->target->setHealth($this->target->getHealth() - $this->damage);
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
                $this->damage,
            ],
            $this->message
        );
    }
}
