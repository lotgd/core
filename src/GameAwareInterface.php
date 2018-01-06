<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * Interface for classes that are aware of the game
 * @package LotGD\Core
 */
interface GameAwareInterface
{
    public function setGame(Game $g);
    public function getGame(): Game;
}