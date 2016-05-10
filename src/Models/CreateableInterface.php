<?php

declare(strict_types = 1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for createable models
 */
interface CreateableInterface
{
    public static function create(array $arguments): CreateableInterface;
    public function save(EntityManagerInterface $em);
}
