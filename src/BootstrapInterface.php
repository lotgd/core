<?php
declare(strict_types=1);

namespace LotGD\Core;

use Symfony\Component\Console\Application;

interface BootstrapInterface
{
    public function hasEntityPath(): bool;
    public function getEntityPath(): string;
    public function addDaenerysCommand(Game $game, Application $application);
}