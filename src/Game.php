<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Models\Character;

class Game
{
    private $entityManager;
    private $eventManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventManager $eventManager)
    {
        $this->entityManager = $entityManager;
        $this->eventManager = $eventManager;
    }

    /**
     * Returns the game's entity manager.
     * @return EntityManagerInterface The game's database entity manager.
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Returns the game's event manager.
     * @return EventManager The game's event manager.
     */
    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }
    
    /**
     * Returns the game's dice bag.
     * @return DiceBag
     */
    public function getDiceBag(): DiceBag
    {
        return $this->diceBag;
    }
    
    /**
     * Returns the active character for this game run
     * @return Character
     */
    public function getCharacter(): Character
    {
        return $this->character;
    }
}
