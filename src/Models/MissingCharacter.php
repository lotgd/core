<?php
declare(strict_types = 1);

namespace LotGD\Core\Models;

/**
 * Description of MissingCharacter
 */
class MissingCharacter implements CharacterInterface
{
    private $displayname;
    
    public function __construct(string $displayname)
    {
        $this->displayname = $displayname;
    }
    
    public function getProperty(string $name, $default = null)
    {
        return $default;
    }
    
    public function getDisplayName(): string
    {
        return $this->displayname;
    }
    
    public function getName(): string
    {
        return $this->displayname;
    }
    
    public function getCharacterViewpoint(): CharacterViewpoint
    {
        throw new IsNullException();
    }
}
