<?php

declare(strict_types = 1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\{
    BuffList,
    Game
};
use LotGD\Core\Exceptions\IsNullException;
use LotGD\Core\Models\CharacterViewpoint;

/**
 * Provides basic implementation to mock CharacterInterface.
 */
trait MockCharacter
{
    public function __call($name, $arguments)
    {
        throw new IsNullException();
    }

    public function getId(): int
    {
        throw new IsNullException();
    }

    public function getName(): string
    {
        throw new IsNullException();
    }

    public function getDisplayName(): string
    {
        throw new IsNullException();
    }

    public function getHealth(): int
    {
        throw new IsNullException();
    }

    public function setHealth(int $amount)
    {
        throw new IsNullException();
    }

    public function damage(int $damage)
    {
        throw new IsNullException();
    }

    public function heal(int $heal, bool $overheal = false)
    {
        throw new IsNullException();
    }

    public function getMaxHealth(): int
    {
        throw new IsNullException();
    }

    public function getLevel(): int
    {
        throw new IsNullException();
    }

    public function isAlive(): bool
    {
        throw new IsNullException();
    }

    public function getAttack(Game $game, bool $ignoreBuffs = false): int
    {
        throw new IsNullException();
    }

    public function getDefense(Game $game, bool $ignoreBuffs = false): int
    {
        throw new IsNullException();
    }

    public function getViewpoint(): CharacterViewpoint
    {
        throw new IsNullException();
    }

    public function getProperty(string $name, $default = null)
    {
        return $default;
    }

    /**
     * Returns an empty bufflist
     * @return BuffList
     */
    public function getBuffs(): BuffList
    {
        throw new IsNullException();
    }
}
