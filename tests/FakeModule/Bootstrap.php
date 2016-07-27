<?php

namespace LotGD\Core\Tests\FakeModule;

use Symfony\Component\Console\Application;

use LotGD\Core\BootstrapInterface;
use LotGD\Core\Game;

class Bootstrap implements BootstrapInterface
{
    public function hasEntityPath(): bool
    {
        return true;
    }
    
    public function getEntityPath(): string
    {
        return __DIR__ . "/Models";
    }
    
    public function addDaenerysCommand(Game $game, Application $application)
    {
        
    }
}
