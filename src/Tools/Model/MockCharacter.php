<?php

declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\BuffList;
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
    public function __call(mixed $name, mixed $arguments)
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return string
     */
    public function getName(): string
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return string
     */
    public function getDisplayName(): string
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return int
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
     * @throws IsNullException
     * @return int
     */
    public function getMaxHealth(): int
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return int
     */
    public function getLevel(): int
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return bool
     */
    public function isAlive(): bool
    {
        throw new IsNullException();
    }

    /**
     * @param bool $ignoreBuffs
     * @throws IsNullException
     * @return int
     */
    public function getAttack(bool $ignoreBuffs = false): int
    {
        throw new IsNullException();
    }

    /**
     * @param bool $ignoreBuffs
     * @throws IsNullException
     * @return int
     */
    public function getDefense(bool $ignoreBuffs = false): int
    {
        throw new IsNullException();
    }

    /**
     * @throws IsNullException
     * @return Viewpoint
     */
    public function getViewpoint(): Viewpoint
    {
        throw new IsNullException();
    }

    /**
     * @param string $name
     * @param null $default
     */
    public function getProperty(string $name, $default = null)
    {
        return $default;
    }

    /**
     * Returns an empty bufflist.
     * @return BuffList
     */
    public function getBuffs(): BuffList
    {
        throw new IsNullException();
    }
}
