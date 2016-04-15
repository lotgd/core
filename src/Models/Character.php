<?php

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="characters")
 */
class Character {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50, unique=true); */
    private $name;
    /** @Column(type="text", unique=true); */
    private $displayName;
    /** @Column(type="integer", options={"default" = 10}) */
    private $health = 10;
    /** @Column(type="integer", options={"default" = 10}) */
    private $maxhealth = 10;
    private $properties;
    
    /**
     * Sets the character's name and generates the display name
     * @param string $name The name to set
     */
    public function setName(string $name) {
        $this->name = $name;
        $this->generateDisplayName();
    }
    
    /**
     * Returns the character's name
     * @return string The name
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * Generates the display name which is a composition of title and name.
     */
    protected function generateDisplayName() {
        $this->displayName = $this->name;
    }
}
