<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * @MappedSuperclass
 */
abstract class BasicEnemy implements FighterInterface
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50); */
    private $name;
    /** @Column(type="integer"); */
    private $level;
    /** @var int */
    private $health;
    
    /**
     * Returns the enemy's id
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Returns the enemy's name
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
     * Returns the enemy's current health
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
     * Returns true if the enemy is alive.
     * @return bool
     */
    public function isAlive(): bool
    {
        if ($this->getHealth() > 0) {
            return true;
        } else {
            return false;
        }
    }
}