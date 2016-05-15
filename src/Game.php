<?php
declare (strict_types=1);

namespace LotGD\Core;

class Game
{
    private $entityManager;
    private $eventManager;

    /**
     * Returns the game's entity manager.
     * @return EntityManagerInterface The game's database entity manager.
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Returns the game's event manager.
     * @return EventManager The game's event manager.
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }
}
