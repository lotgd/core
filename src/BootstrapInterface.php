<?php
declare(strict_types=1);

namespace LotGD\Core;

interface BootstrapInterface
{
    public function hasEntityPath(): bool;
    public function getEntityPath(): string;
}