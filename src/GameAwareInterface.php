<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * Interface for classes that are aware of the game.
 */
interface GameAwareInterface
{
    public function setGame(Game $g);
    public function getGame(): Game;
}
