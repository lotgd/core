<?php

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\Creator;
use Doctrine\ORM\Mapping\Entity;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="characters")
 */
class Character {
    use Creator;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50, unique=true); */
    private $name;
    /** @Column(type="text", unique=true); */
    private $displayName;
    /** @Column(type="integer", options={"default" = 10}) */
    private $maxhealth = 10;
    /** @Column(type="integer", options={"default" = 10}) */
    private $health;
    private $properties;
    
    /** @var array */
    protected static $fillable = [
        "name",
        "maxhealth",
    ];
    
    /**
     * Returns the entity's id
     * @return int The id
     */
    public function getId(): int {
        return $this->id;
    }
    
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
    
    /**
     * Sets the maximum health of a character to a given value. It also sets the
     * health if none has been set yet.
     * @param int $maxhealth
     */
    protected function setMaxhealth(int $maxhealth) {
        $this->maxhealth = $maxhealth;
        $this->health = $this->health??$this->maxhealth;
    }
}
