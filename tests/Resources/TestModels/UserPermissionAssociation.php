<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Ressources\TestModels;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Models\PermissionAssociationInterface;
use LotGD\Core\Tools\Model\PermissionAssociationable;

/**
 * @Entity
 * @Table("TestUserAssociations")
 */
class UserPermissionAssociation implements PermissionAssociationInterface {
    use PermissionAssociationable;

    /**
     * @Id @ManyToOne(targetEntity="User", inversedBy="permissions")
     * @JoinColumn(name="owner", referencedColumnName="id")
     */
    private $owner;
}