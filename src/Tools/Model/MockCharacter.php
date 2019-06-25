<?php

declare(strict_types = 1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\{
    BuffList,
    Game
};
use LotGD\Core\Exceptions\IsNullException;
use LotGD\Core\Models\Viewpoint;
use Ramsey\Uuid\UuidInterface;

/**
 * Provides basic implementation to mock CharacterInterface.
 */
trait MockCharacter
{
    /**
     * @param mixed $name
     * @param mixed $arguments
     * @throws IsNullException
     */
    public function __call($name, $arguments)
    {
        throw new IsNullException();
    }

    /**
     * @return UuidInterface
     * @throws IsNullException
     */
    public function getId(): UuidInterface
    {
        throw new IsNullException();
    }

    /**
     * @return string
     * @throws IsNullException
     */
    public function getName(): string
    {
        throw new IsNullException();
    }

    /**
     * @return string
     * @throws IsNullException
     */
    public function getDisplayName(): string
    {
        throw new IsNullException();
    }

    /**
     * @return int
     * @throws IsNullException
     */
    public function getHealth(): int
    {
        throw new IsNullException();
    }

    /**
     * @param int $amount
     * @throws IsNullException
     */
    public function setHealth(int $amount)
    {
        throw new IsNullException();
    }

    /**
     * @param int $damage
     * @throws IsNullException
     */
    public function damage(int $damage)
    {
        throw new IsNullException();
    }

    /**
     * @param int $heal
     * @param bool $overheal
     * @throws IsNullException
     */
    public function heal(int $heal, bool $overheal = false)
    {
        throw new IsNullException();
    }

    /**
     * @return int
     * @throws IsNullException
     */
    public function getMaxHealth(): int
    {
        throw new IsNullException();
    }

    /**
     * @return int
     * @throws IsNullException
     */
    public function getLevel(): int
    {
        throw new IsNullException();
    }

    /**
     * @return bool
     * @throws IsNullException
     */
    public function isAlive(): bool
    {
        throw new IsNullException();
    }

    /**
     * @param bool $ignoreBuffs
     * @return int
     * @throws IsNullException
     */
    public function getAttack(bool $ignoreBuffs = false): int
    {
        throw new IsNullException();
    }

    /**
     * @param bool $ignoreBuffs
     * @return int
     * @throws IsNullException
     */
    public function getDefense(bool $ignoreBuffs = false): int
    {
        throw new IsNullException();
    }

    /**
     * @return Viewpoint
     * @throws IsNullException
     */
    public function getViewpoint(): Viewpoint
    {
        throw new IsNullException();
    }

    /**
     * @param string $name
     * @param null $default
     * @return null
     */
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
