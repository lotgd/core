<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Ressources\TestModels;

use Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use Lotgd\Core\Models\Actor;


/**
 * @Entity
 * @Table("TestUsers")
 */
class User extends Actor #implements PermissionableInterface {
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50); */
    private $name;
    /** @OneToMany(targetEntity="UserPermissionAssociation", mappedBy="owner", cascade={"persist", "remove"}, orphanRemoval=true) */
    protected $permissions;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

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

    public function getActorName(): string
    {
        return "User #".$this->id." (".$this->name.")";
    }

    protected function getPermissionAssociationClass(): string
    {
        return UserPermissionAssociation::class;
    }

    protected function getPermissionAssociations(): Generator
    {
        foreach ($this->permissions as $permission) {
            yield $permission;
        }
    }
}
