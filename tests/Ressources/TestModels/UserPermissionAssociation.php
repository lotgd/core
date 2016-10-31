<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Ressources\TestModels;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Models\PermissionAssociation;
use LotGD\Core\Tools\Model\PermissionAssociationable;

/**
 * @Entity
 * @Table("TestUserAssociations")
 */
class UserPermissionAssociation {
    use PermissionAssociationable;

    /**
     * @Id @ManyToOne(targetEntity="User", inversedBy="permissions") 
     */
    private $owner;
}