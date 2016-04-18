<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\{Creator, Deletor};
use Doctrine\ORM\Mapping\Entity;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="characters")
 */
class Character {
    use Creator;
    use Deletor;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50, unique=true); */
    private $name;
    /** @Column(type="text", unique=true); */
    private $displayName;
    /** @Column(type="integer", options={"default" = 10}) */
    private $maxHealth = 10;
    /** @Column(type="integer", options={"default" = 10}) */
    private $health = 10;
    private $properties;
    
    /** @var array */
    private static $fillable = [
        "name",
        "maxHealth",
    ];
    
    /**
     * Creates a character at full health
     * @see LotGD\Core\Tools\Model\Creator
     */
    public static function createAtFullHealth(array $arguments): self {
        $newCharacter = self::create($arguments);
        $newCharacter->setHealth($newCharacter->getMaxHealth());
        
        return $newCharacter;
    }
    
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
     * Returns displayName, a combination of title, name and suffix, mixed with colour codes
     * @return string The displayName
     */
    public function getDisplayName(): string {
        return $this->displayName;
    }
    
    /**
     * Sets the maximum health of a character to a given value. It also sets the
     * health if none has been set yet.
     * @param int $maxHealth
     */
    public function setMaxHealth(int $maxHealth) {
        $this->maxHealth = $maxHealth;
    }
    
    /**
     * Returns the maximum health
     * @return int
     */
    public function getMaxHealth(): int {
        return $this->maxHealth;
    }
    
    /**
     * Sets current health
     * @param int $health
     */
    public function setHealth(int $health) {
        $this->health = $health;
    }
    
    /**
     * Returns current health
     * @return int
     */
    public function getHealth(): int {
        return $this->health;
    }
}
