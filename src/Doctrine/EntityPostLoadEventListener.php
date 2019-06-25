<?php
declare(strict_types=1);

namespace LotGD\Core\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use LotGD\Core\Game;
use LotGD\Core\GameAwareInterface;

/**
 * Class EntityPostLoadEventListener.
 */
class EntityPostLoadEventListener
{
    /** @var Game $game */
    private $game;

    /**
     * EntityPostLoadEventListener constructor.
     * @param Game $g
     */
    public function __construct(Game $g)
    {
        $this->game = $g;
    }

    /**
     * Called upon event postLoad.
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof GameAwareInterface) {
            $entity->setGame($this->game);
        }
    }
}
