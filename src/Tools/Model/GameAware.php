<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\Game;

/**
 * Helper trait to implement public setGame from GameAwareInterface and private getGame for internal use.
 */
trait GameAware
{
    private Game $game;

    /**
     * @param Game $game
     */
    public function setGame(Game $game)
    {
        $this->game = $game;
    }

    /**
     * @return Game
     */
    public function getGame(): Game
    {
        return $this->game;
    }
}
