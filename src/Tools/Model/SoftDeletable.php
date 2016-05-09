<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides methods for deleting entities.
 */
trait SoftDeletable {
    /** @Column(type="datetime", nullable=true) */
    private $deletedAt;
    
    /**
     * Deletes the entity
     * @param EntityManagerInterface $em
     */
    public function delete(EntityManagerInterface $em)
    {
        $this->setDeletedAt(new DateTime("now"));
        $em->flush();
    }
    
    public function restore(EntityManagerInterface $em)
    {
        $this->setDeletedAt(null);
        $em->flush();
    }
    
    public function setDeletedAt(DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;
    }
    
    public function getDeletedAt(): DateTime
    {
        return $this->deletedAt;
    }
    
    public function isSoftDeleted(): bool
    {
        return is_null($this->deletedAt) ? false : true;
    }
}
