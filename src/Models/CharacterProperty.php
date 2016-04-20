<?php

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\Properties;

/**
 * Properties for Characters
 * @Entity
 * @Table(name="character_properties")
 */
class CharacterProperty {
    use Properties;
    
    /** @Id @ManyToOne(targetEntity="Character") */
    private $owner;
            
    /**
     * Returns the owner
     * @return \LotGD\Core\Models\Character
     */
    public function getOwner(): Character
    {
        return $this->owner;
    }
    
    /**
     * Sets the owner
     * @param \LotGD\Core\Models\Character $owner
     */
    public function setOwner(Character $owner)
    {
        $this->owner = $owner;
    }
}
