<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

/**
 * Implement this interface if an entity has associates permissions.
 */
interface PermissionableInterface
{
    /**
     * Returns true if objects has a entry related to a given permission.
     * @param string $permissionId
     * @return bool
     */
    public function hasPermission(string $permissionId): bool;

    /**
     * Returns the permission association.
     * @param string $permissionId
     * @return PermissionAssociationInterface
     */
    public function getPermission(string $permissionId): PermissionAssociationInterface;

    /**
     * Returns the raw permission entity.
     * @param string $permissionId
     * @return Permission
     */
    public function getRawPermission(string $permissionId): Permission;

    /**
     * Adds a permission with a set state.
     * @param \LotGD\Core\Models\Permission $permission
     */
    public function addPermission(Permission $permission, int $state);

    /**
     * Removes a permission with a given id.
     * @param string $permissionId
     */
    public function removePermission(string $permissionId);
}
