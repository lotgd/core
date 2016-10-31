<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\Models\Permission;

/**
 * Tools to work with a permission type field.
 */
trait PermissionAssociationable
{
    /**
     * @Id @ManyToOne(targetEntity="LotGD\Core\Models\Permission", inversedBy="permission")
     * @JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected $permission;
    /** @Column(type="integer") */
    protected $permissionState;

    public function getId(): string
    {
        return $this->permission->getId();
    }

    public function getLibrary(): string
    {
        return $this->permission->getLibrary();
    }

    public function getState(): int
    {
        return $this->permissionState;
    }

    public function checkState(int $state): bool
    {
        return $this->permissionState == $state ? true : false;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
