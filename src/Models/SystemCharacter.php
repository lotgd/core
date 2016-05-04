<?php

declare(strict_types = 1);

namespace LotGD\Core\Models;

use LotGD\Core\Exceptions\IsNullException;

/**
 * Description of SystemCharacter
 */
class SystemCharacter implements CharacterInterface
{
    static $instance = null;
    static $characterName = "System";
    
    public static function getInstance()
    {
        self::$instance = self::$instance ?? new self();
        
        return self::$instance;
    }
    
    private function __construct()
    {    
    }
    
    public function getDisplayName(): string
    {
        return self::$characterName;
    }
    
    public function getName(): string
    {
        return self::$characterName;
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
