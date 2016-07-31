<?php
declare(strict_types=1);

namespace LotGD\Core\Models\BattleEvents;

use LotGD\Core\Exceptions\BattleEventException;
use LotGD\Core\Models\FighterInterface;

/**
 * Battle event that represents damage to a minion.
 */
class MinionDamageEvent extends BattleEvent
{
    protected $target;
    protected $damage;
    protected $message;

    /**
     * Construct a MinionDamageEvent against $target, with damage $damage
     * and message $message.
     * $message can contain '{target}' and '{amount}'
     * which will be replaced by the name of the target and the damage, respectively.
     * @param FighterInterface $target
     * @param int $damage
     * @param string $message
     */
    public function __construct(
        FighterInterface $target,
        int $damage,
        string $message
    ) {
        $this->target = $target;
        $this->damage = $damage;
        $this->message = $message;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function apply()
    {
        parent::apply();
        $this->target->setHealth($this->target->getHealth() - $this->damage);
    }
}
