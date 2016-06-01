<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\{
    BuffList,
    Game
};
use LotGD\Core\Tools\Exceptions\BuffSlotOccupiedException;
use LotGD\Core\Tools\Model\{
    Creator,
    PropertyManager,
    SoftDeletable
};

/**
 * Model for a character
 *
 * @Entity(repositoryClass="LotGD\Core\Models\Repositories\CharacterRepository")
 * @Table(name="characters")
 */
class Character implements CharacterInterface, CreateableInterface
{
    use Creator;
    use SoftDeletable;
    use PropertyManager;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50); */
    private $name;
    /** @Column(type="text"); */
    private $displayName;
    /** @Column(type="integer", options={"default":10}) */
    private $maxHealth = 10;
    /** @Column(type="integer", options={"default":10}) */
    private $health = 10;
    /** @Column(type="integer", options={"default":1})/ */
    private $level = 1;
    /** @OneToMany(targetEntity="CharacterProperty", mappedBy="owner", cascade={"persist"}) */
    private $properties;
    /** @OneToMany(targetEntity="CharacterViewpoint", mappedBy="owner", cascade={"persist"}) */
    private $characterViewpoint;
    /** 
     * @ManyToMany(targetEntity="MessageThread", inversedBy="participants", cascade={"persist"})
     * @JoinTable(
     *  name="message_threads_x_characters",
     *  joinColumns={
     *      @JoinColumn(name="character_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @JoinColumn(name="messagethread_id", referencedColumnName="id")
     *  }
     * )
     */
    private $messageThreads;
    /** @OneToMany(targetEntity="Buff", mappedBy="character", cascade={"persist"}) */
    private $buffs;
    /** @var BuffList */
    private $buffList;
    
    /** @var array */
    private static $fillable = [
        "name",
        "maxHealth",
        "level",
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
    
    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->characterViewpoint = new ArrayCollection();
        $this->buffs = new ArrayCollection();
        $this->messageThreads = new ArrayCollection();
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
    
    /**
     * Does damage to the entity.
     * @param int $damage
     */
    public function damage(int $damage)
    {
        $this->health -= $damage;
        
        if ($this->health < 0) {
            $this->health = 0;
        }
    }
    
    /**
     * Heals the enemy
     * @param int $heal
     * @param type $overheal True if healing bigger than maxhealth is desired.
     */
    public function heal(int $heal, bool $overheal = false)
    {
        $this->health += $heal;
        
        if ($this->health > $this->getMaxHealth() && $overheal === false) {
            $this->health = $this->getMaxHealth();
        }
    }
    
    /**
     * Returns true if the character is alive.
     * @return bool
     */
    public function isAlive(): bool
    {
        return $this->getHealth() > 0;
    }
    
    /**
     * Returns the character's level
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }
    
    /**
     * Returns the character's virtual attribute "attack"
     */
    public function getAttack(Game $game, bool $ignoreBuffs = false): int
    {
        return $this->level * 2;
    }
    
    /**
     * Returns the character's virtual attribute "defense"
     */
    public function getDefense(Game $game, bool $ignoreBuffs = false): int
    {
        return $this->level * 2;
    }
    
    /**
     * Sets the character's level
     * @param int $level
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
    }
    
    /**
     * Returns the current character scene and creates one if it is non-existant
     * @return \LotGD\Core\Models\CharacterViewpoint
     */
    public function getCharacterViewpoint(): CharacterViewpoint
    {
        if (count($this->characterViewpoint) === 0) {
            $characterScene = CharacterViewpoint::Create(["owner" => $this]);
            $this->characterViewpoint->add($characterScene);
        }
        
        return $this->characterViewpoint->first();
    }
    
    /**
     * Returns a list of buffs
     */
    public function getBuffs(): BuffList
    {
        $this->buffList = $this->buffList ?? new BuffList($this->buffs);
        return $this->buffList;
    }
    
    /**
     * Adds a buff to the buffList
     */
    public function addBuff(Buff $buff, bool $override = false)
    {
        try {
            $this->getBuffs()->add($buff);
        } catch(BuffSlotOccupiedException $e) {
            $this->getBuffs()->renew($buff);
        }
    }
    
    /**
     * Returns a list of message threads this user has created.
     * @return Collection
     */
    public function getMessageThreads(): Collection
    {
        return $this->messageThreads;
    }
}
