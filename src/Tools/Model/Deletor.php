<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Exceptions\{
    AttributeMissingException,
    WrongTypeException
};

/**
 * Provides methods for deleting entities.
 */
trait Deletor {
    /**
     * Deletes the entity
     * @param EntityManagerInterface $em
     */
    public function delete(EntityManagerInterface $em) {
        $em->remove($this);
        $em->flush();
    }
}
