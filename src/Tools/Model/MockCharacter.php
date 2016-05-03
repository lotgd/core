<?php

declare(strict_types = 1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\Exceptions\IsNullException;
use LotGD\Core\Models\CharacterViewpoint;

/**
 * Provides basic implementation to mock CharacterInterface.
 */
trait MockCharacter
{
    public function __call($name, $arguments) {
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
    
    public function getMaxHealth(): int
    {
        throw new IsNullException();
    }
    
    public function getCharacterViewpoint(): CharacterViewpoint
    {
        throw new IsNullException();
    }
    
    public function getProperty(string $name, $default = null)
    {
        return $default;
    }
}
