<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;
use LotGD\Core\Tools\Model\PropertyManager;
use LotGD\Core\Tools\Model\SoftDeletable;
use LotGD\Core\Models\Repositories\CharacterRepository;

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
    /** @Column(type="integer", options={"default" = 10}) */
    private $maxHealth = 10;
    /** @Column(type="integer", options={"default" = 10}) */
    private $health = 10;
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
    
    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->characterViewpoint = new ArrayCollection();
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
     * Returns a list of message threads this user has created.
     * @return Collection
     */
    public function getMessageThreads(): Collection
    {
        return $this->messageThreads;
    }
    
    public function sendMessageTo(Character $recipient)
    {
        // ToDo: implement later
        throw new \LotGD\Core\Exceptions\NotImplementedException;
    }
    
    public function receiveMessageFrom(Character $author)
    {
        // ToDo: implement later
        throw new \LotGD\Core\Exceptions\NotImplementedException;
    }
}
