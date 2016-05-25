<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use LotGD\Core\Game;

/**
 * Interface for models that should be able to participate in fights.
 */
interface FighterInterface
{
    public function getDisplayName(): string;
    public function getLevel(): int;
    public function getMaxHealth(): int;
    public function getHealth(): int;
    public function isAlive(): bool;
    public function getAttack(Game $game, bool $ignoreBuffs = false): int;
    public function getDefense(Game $game, bool $ignoreBuffs = false): int;
    public function damage(int $damage);
    public function heal(int $heal);
}
