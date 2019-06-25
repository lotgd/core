<?php

declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for createable models.
 */
interface SaveableInterface
{
    public static function _save(self $object, EntityManagerInterface $em);
    public function save(EntityManagerInterface $em);
}
