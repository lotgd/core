<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use LotGD\Core\Game;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;

/**
 * Parent class for daenerys tool commands.
 */
abstract class BaseCommand extends Command
{
    protected $game;

    /**
     * Construct the command, using the provided Game.
     * @param Game $game
     */
    public function __construct(Game $game)
    {
        parent::__construct();
        $this->game = $game;
    }

    /**
     * Returns a cloned logger with a different context name.
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->game->getLogger()->withName("daenerys-cli");
    }
}
