<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;


use LotGD\Core\Game;

/**
 * Helper trait to implement public setGame from GameAwareInterface and private getGame for internal use.
 * @package LotGD\Core\Tools\Model
 */
trait GameAware
{
    private $game;

    public function setGame(Game $game) {
        $this->game = $game;
    }

    private function getGame(): Game {
        return $this->game;
    }
}