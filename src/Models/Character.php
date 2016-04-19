<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;
use LotGD\Core\Tools\Model\PropertyManager;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="characters")
 */
class Character
{
    use Creator;
    use Deletor;
    use PropertyManager;
    
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
    /** @OneToMany(targetEntity="CharacterProperty", mappedBy="owner", cascade={"persist"}) */
    private $properties;
    
    /** @var string fqcn of the property sub class */
    private static $propertyClass = CharacterProperty::class;
    
    /** @var array */
    private static $fillable = [
        "name",
        "maxHealth",
    ];
    
    /**
     * Creates a character at full health
     */
    public static function createAtFullHealth(array $arguments): self
    {
        $newCharacter = self::create($arguments);
        $newCharacter->setHealth($newCharacter->getMaxHealth());
        return $newCharacter;
    }
    
    public function __construct() {
        $this->properties = new ArrayCollection();
    }
    
    /**
     * Returns the entity's id
     * @return int The id
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Sets the character's name and generates the display name
     * @param string $name The name to set
     */
    public function setName(string $name)
    {
        $this->name = $name;
        $this->generateDisplayName();
    }
    
    /**
     * Returns the character's name
     * @return string The name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Generates the display name which is a composition of title and name.
     */
    protected function generateDisplayName()
    {
        $this->displayName = $this->name;
    }
    
    /**
     * Returns displayName, a combination of title, name and suffix, mixed with colour codes
     * @return string The displayName
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    
    /**
     * Sets the maximum health of a character to a given value. It also sets the
     * health if none has been set yet.
     * @param int $maxhealth
     */
    public function setMaxHealth(int $maxHealth)
    {
        $this->maxHealth = $maxHealth;
    }
    
    /**
     * Returns the maximum health
     * @return int
     */
    public function getMaxHealth(): int
    {
        return $this->maxHealth;
    }
    
    /**
     * Sets current health
     * @param int $health
     */
    public function setHealth(int $health)
    {
        $this->health = $health;
    }
    
    /**
     * Returns current health
     * @return int
     */
    public function getHealth(): int
    {
        return $this->health;
    }
}
