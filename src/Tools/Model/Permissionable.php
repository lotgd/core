<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\Exceptions\PermissionAlreadyExistsException;
use LotGD\Core\Exceptions\PermissionDoesNotExistException;
use LotGD\Core\Models\Permission;
use LotGD\Core\Models\PermissionAssociationInterface;


/**
 * Tools to work with a permission type field.
 */
trait Permissionable
{
    /** @var array Associations between permission-id and PermissionAssociation entity. */
    private $_permissions = [];

    protected function loadPermissions()
    {
        if (empty($this->permissionAssociationEntity)) {
            throw new PermissionAssociationEntityMissingException(
                "The permissionable entity does not have the property permissionAssociationEntity set."
            );
        }

        if (empty($this->_permissions)) {
            foreach ($this->permissions as $permission) {
                $this->_permissions[$permission->getId()] = $permission;
            }
        }
    }

    public function hasPermission(string $permissionId): bool
    {
        $this->loadPermissions();

        return isset($this->_permissions[$permissionId]);
    }

    public function getPermission(string $permissionId): PermissionAssociationInterface
    {
        $this->loadPermissions();

        return $this->_permissions[$permissionId];
    }

    public function getRawPermission(string $permissionId): Permission
    {
        $this->loadPermissions();

        return $this->_permissions[$permissionId]->getPermission();
    }

    public function addPermission(Permission $permission, int $state)
    {
        $this->loadPermissions();

        if ($this->hasPermission($permission->getId())) {
            $permissionId = $permission->getId();
            throw new PermissionAlreadyExistsException("The permission with the id {$permissionId} has already been set on this actor.");
        } else {
            $permissionAssoc = new $this->permissionAssociationEntity($this, $permission, $state);
            $this->permissions->add($permissionAssoc);
            $this->_permissions[$permissionAssoc->getId()] = $permissionAssoc;
        }
    }

    public function removePermission(string $permissionId)
    {
        $this->loadPermissions();

        if ($this->hasPermission($permissionId)) {
            $permissionAssoc = $this->getPermission($permissionId);
            $this->permissions->removeElement($permissionAssoc);
            unset($this->_permissions[$permissionId]);
        } else {
            throw new PermissionDoesNotExistException("The permission with the id {$permissionId} has not been set on this actor.");
        }
    }
}
