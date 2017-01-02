<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\Models\Actor;
use LotGD\Core\Models\Permission;
use LotGD\Core\Models\PermissionableInterface;

/**
 * Tools to work with a permission type field.
 */
trait PermissionAssociationable
{
    /**
     * @Id @ManyToOne(targetEntity="LotGD\Core\Models\Permission", inversedBy="permission")
     * @JoinColumn(name="permission", referencedColumnName="id")
     */
    protected $permission;
    /** @Column(type="integer") */
    protected $permissionState;

    public function __construct(Actor $owner, Permission $permission, int $state) {
        $this->owner = $owner;
        $this->permission = $permission;
        $this->permissionState = $state;
    }

    /**
     * Returns the current state of the permission.
     * @return int
     */
    public function getState(): int
    {
        return $this->permissionState;
    }

    /**
     * Sets the current state of the permission.
     * @param int $state
     */
    public function setState(int $state)
    {
        $this->permissionState = $state;
    }

    /**
     * Checks if this permission is set to a given state.
     * @param int $state
     * @return bool
     */
    public function checkState(int $state): bool
    {
        return $this->permissionState == $state ? true : false;
    }

    /**
     * Returns the permission id.
     * @see Permission->getId()
     * @return string
     */
    public function getId(): string
    {
        return $this->permission->getId();
    }

    /**
     * Returns the permission library.
     * @see Permission->getLibrary()
     * @return string
     */
    public function getLibrary(): string
    {
        return $this->permission->getLibrary();
    }

    /**
     * Returns the Permission entity.
     * @return Permission
     */
    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
