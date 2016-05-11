<?php

declare(strict_types = 1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for createable models
 */
interface CreateableInterface extends SaveableInterface
{
    public static function create(array $arguments): CreateableInterface;
}
