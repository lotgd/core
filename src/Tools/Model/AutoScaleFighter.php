<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

/**
 * Automatically calculated values based on the fighter's level
 */
trait AutoScaleFighter
{   
    /**
     * Returns the maximum health based on the fighter's level
     * @return int
     */
    public function getMaxHealth(): int
    {
        $level = $this->getLevel();
        return ($level * 10) + (int)ceil(($level + 1) / 2) - 1;
    }
    
    /**
     * Returns the attack value based on the fighter's level
     * @return int
     */
    public function getAttack(): int
    {
        $level = $this->getLevel();
        return (int)$level * 2 - 1;
    }
    
    /**
     * Returns the defense value based on the fighter's level
     * @return int
     */
    public function getDefense(): int
    {
        $level = $this->getlevel();
        return (int)floor($level*1.45);
    }
}
