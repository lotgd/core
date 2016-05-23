<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

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
    public function getAttack(): int;
    public function getDefense(): int;
    public function damage(int $damage);
    public function heal(int $heal);
}
