<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\BuffList;
use LotGD\Core\Events\CharacterEventData;
use LotGD\Core\Exceptions\BuffSlotOccupiedException;
use LotGD\Core\GameAwareInterface;
use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\ExtendableModel;
use LotGD\Core\Tools\Model\GameAware;
use LotGD\Core\Tools\Model\PropertyManager;
use LotGD\Core\Tools\Model\SoftDeletable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Model for a character.
 *
 * @Entity(repositoryClass="LotGD\Core\Models\Repositories\CharacterRepository")
 * @Table(name="characters")
 */
class Character implements CharacterInterface, CreateableInterface, GameAwareInterface, ExtendableModelInterface
{
    use Creator;
    use SoftDeletable;
    use PropertyManager;
    use GameAware;
    use ExtendableModel;

    /** @Id @Column(type="uuid", unique=True) */
    private $id;
    /** @Column(type="string", length=50); */
    private $name = "";
    /** @Column(type="text"); */
    private $displayName = "";
    /** @Column(type="integer", options={"default"=10}) */
    private $maxHealth = 10;
    /** @Column(type="integer", options={"default"=10}) */
    private $health = 10;
    /** @Column(type="integer", options={"default"=1})/ */
    private $level = 1;
    /** @OneToMany(targetEntity="CharacterProperty", mappedBy="owner", cascade={"persist", "remove"}) */
    private $properties;
    /** @OneToOne(targetEntity="Viewpoint", mappedBy="owner", cascade={"persist", "remove"}) */
    private $viewpoint;
    /**
     * @ManyToMany(targetEntity="MessageThread", inversedBy="participants", cascade={"persist"})
     * @JoinTable(
     *     name="message_threads_x_characters",
     *     joinColumns={
     *         @JoinColumn(name="character_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *         @JoinColumn(name="messagethread_id", referencedColumnName="id")
     *     }
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

    private $propertyClass = CharacterProperty::class;

    /**
     * Creates a character at full health.
     */
    public static function createAtFullHealth(array $arguments): self
    {
        $newCharacter = self::create($arguments);
        $newCharacter->setHealth($newCharacter->getMaxHealth());
        return $newCharacter;
    }

    /**
     * Construct an empty character.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4();

        $this->properties = new ArrayCollection();
        $this->buffs = new ArrayCollection();
        $this->messageThreads = new ArrayCollection();
    }

    /**
     * Returns the entity's id.
     * @return int The id
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Sets the character's name and generates the display name.
     * @param string $name The name to set
     */
    public function setName(string $name)
    {
        $this->name = $name;
        $this->generateDisplayName();
    }

    /**
     * Returns the character's name.
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
     * Returns displayName, a combination of title, name and suffix, mixed with colour codes.
     * @return string The displayName
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Sets the maximum health of a character to a given value. It also sets the
     * health if none has been set yet.
     * @param int $maxHealth
     */
    public function setMaxHealth(int $maxHealth)
    {
        $this->maxHealth = $maxHealth;
    }

    /**
     * Returns the maximum health.
     * @return int
     */
    public function getMaxHealth(): int
    {
        return $this->maxHealth;
    }

    /**
     * Sets current health.
     * @param int $health
     */
    public function setHealth(int $health)
    {
        $this->health = $health;
    }

    /**
     * Returns current health.
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
     * Heals the enemy.
     * @param int $heal
     * @param bool $overheal True if healing bigger than maxHealth is desired.
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
     * Returns the character's level.
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Returns the character's virtual attribute "attack".
     * @param bool $ignoreBuffs
     * @return int
     */
    public function getAttack(bool $ignoreBuffs = false): int
    {
        $baseAttack = $this->level;

        $hookData = $this->getGame()->getEventManager()->publish(
            "h/lotgd/core/getCharacterAttack",
            CharacterEventData::create([
                "character" => $this,
                "value" => $baseAttack,
            ])
        );

        $modifiedAttack = $hookData->get("value");

        return $modifiedAttack;
    }

    /**
     * Returns the character's virtual attribute "defense".
     * @param bool $ignoreBuffs
     * @return int
     */
    public function getDefense(bool $ignoreBuffs = false): int
    {
        $baseDefense = $this->level;

        $hookData = $this->getGame()->getEventManager()->publish(
            "h/lotgd/core/getCharacterDefense",
            CharacterEventData::create([
                "character" => $this,
                "value" => $baseDefense,
            ])
        );

        $modifiedDefense = $hookData->get("value");

        return $modifiedDefense;
    }

    /**
     * Sets the character's level.
     * @param int $level
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
    }

    /**
     * Returns the current character viewpoint or null if one is not set.
     * @return \LotGD\Core\Models\Viewpoint|null
     */
    public function getViewpoint(): ?Viewpoint
    {
        return $this->viewpoint;
    }

    /**
     * Sets the current character viewpoint.
     */
    public function setViewpoint(?Viewpoint $v)
    {
        $this->viewpoint = $v;
    }

    /**
     * Returns a list of buffs.
     */
    public function getBuffs(): BuffList
    {
        $this->buffList = $this->buffList ?? new BuffList($this->buffs);
        return $this->buffList;
    }

    /**
     * Adds a buff to the buffList.
     */
    public function addBuff(Buff $buff, bool $override = false)
    {
        try {
            $this->getBuffs()->add($buff);
        } catch (BuffSlotOccupiedException $e) {
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
