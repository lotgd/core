<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Ressources\TestModels;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Models\PermissionableInterface;
use LotGD\Core\Tools\Model\Permissionable;

/**
 * @Entity
 * @Table("TestUsers")
 */
class User implements PermissionableInterface {
    use Permissionable;

    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50); */
    private $name;
    /** @OneToMany(targetEntity="UserPermissionAssociation", mappedBy="owner", cascade={"persist", "remove"}) */
    private $permissions;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}
