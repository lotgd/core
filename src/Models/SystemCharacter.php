<?php

declare(strict_types = 1);

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\MockCharacter;

/**
 * Provides a basic system character to provide system information.
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
}
