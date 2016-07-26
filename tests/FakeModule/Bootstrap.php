<?php

namespace LotGD\Core\Tests\FakeModule;

use LotGD\Core\BootstrapInterface;

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
}
