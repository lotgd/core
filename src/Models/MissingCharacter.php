<?php
declare(strict_types = 1);

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\MockCharacter;

/**
 * Provides a basic implementation of CharacterInterface to return the most
 * important data a missing character might still need.
 */
class MissingCharacter implements CharacterInterface
{
    use MockCharacter;
    
    private $displayname;
    
    /**
     * Sets the name of the missing character, defautls to "Nobody"
     * @param string $displayname
     */
    public function __construct(string $displayname = "Nobody")
    {
        $this->displayname = $displayname;
    }
    
    /**
     * Returns the name
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayname;
    }
    
    /**
     * Returns the name
     * @return string
     */
    public function getName(): string
    {
        return $this->displayname;
    }
}
