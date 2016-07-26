<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Symfony\Component\Console\Command\Command;

use LotGD\Core\Game;

abstract class BaseCommand extends Command
{
    protected $game;
    
    public function __construct(Game $game)
    {
        parent::__construct();
        $this->game = $game;
    }
}