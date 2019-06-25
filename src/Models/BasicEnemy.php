<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @MappedSuperclass
 */
abstract class BasicEnemy implements FighterInterface
{
    /** @Id @Column(type="uuid", unique=True) */
    protected $id;
    /** @Column(type="string", length=50); */
    protected $name;
    /** @Column(type="integer"); */
    protected $level;
    /** @var int */
    protected $health;

    /**
     * BasicEnemy constructor. Sets uuid upon creation.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }
    
    /**
     * Returns the enemy's id.
     * @return int
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }
    
    /**
     * Returns the enemy's name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the enemy's display name - this is the same than the name.
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the enemy's level.
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }
    
    /**
     * Returns the enemy's current health.
     * @return int
     */
    public function getHealth(): int
    {
        if ($this->health === null) {
            $this->health = $this->getMaxHealth();
        }
        
        return $this->health;
    }
    
    /**
     * Sets the enemy's current health.
     * @param int $health
     */
    public function setHealth(int $health)
    {
        $this->health = $health;
        
        if ($this->health < 0) {
            $this->health = 0;
        }
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
     * Returns true if the enemy is alive.
     * @return bool
     */
    public function isAlive(): bool
    {
        if ($this->getHealth() > 0) {
            return true;
        }
        return false;
    }
}
