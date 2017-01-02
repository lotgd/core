<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Ressources\TestModels;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use Lotgd\Core\Models\Actor;
#use LotGD\Core\Models\PermissionableInterface;
#use LotGD\Core\Tools\Model\Permissionable;


/**
 * @Entity
 * @Table("TestUsers")
 */
class User extends Actor #implements PermissionableInterface {
{
    #use Permissionable;

    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50); */
    private $name;
    /** @OneToMany(targetEntity="UserPermissionAssociation", mappedBy="owner", cascade={"persist", "remove"}, orphanRemoval=true) */
    protected $permissions;

    protected $permissionAssociationEntity = UserPermissionAssociation::class;

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
}
