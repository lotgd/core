<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Models\SaveableInterface;

/**
 * Provides methods for creating new entities
 */
trait Saveable
{
    /**
     * Static, protected save function to call from trait-overwriting methods.
     * @param \LotGD\Core\Tools\Model\CreateableInterface $object
     * @param EntityManagerInterface $em
     */
    public static function _save(SaveableInterface $object, EntityManagerInterface $em)
    {
        $em->persist($object);
        $em->flush();
    }
    
    /**
     * Marks the entity as permanent and saves it into the database.
     * @param EntityManagerInterface $em The Entity Manager
     */
    public function save(EntityManagerInterface $em)
    {
        self::_save($this, $em);
    }
}
