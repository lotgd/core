<?php

declare(strict_types = 1);

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\MockCharacter;

/**
 * Provides a basic system character to serve as an anonymous user.
 *
 * Whenever a message should be sent by the System instead of a standard character,
 * this class is returned by the entity containing the message instead of a standard
 * character instance.
 */
class SystemCharacter implements CharacterInterface
{
    use MockCharacter;

    static $instance = null;
    static $characterName = "System";

    /**
     * Return an instance of SystemCharacter.
     * @return SystemCharacter
     */
    public static function getInstance()
    {
        self::$instance = self::$instance ?? new self();

        return self::$instance;
    }

    /**
     * Private constructor. Use the static method getInstance().
     */
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function getDisplayName(): string
    {
        return self::$characterName;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::$characterName;
    }
}
