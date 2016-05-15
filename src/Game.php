<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;

class Game
{
    private $entityManager;
    private $eventManager;

    /**
     * Returns the game's entity manager.
     * @return EntityManagerInterface The game's database entity manager.
     */
    public function db(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Returns the game's event manager.
     * @return EventManager The game's event manager.
     */
    public function events(): EventManager
    {
        return $this->eventManager;
    }
}
