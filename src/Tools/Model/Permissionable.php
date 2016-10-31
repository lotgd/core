<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\Models\Permission;

/**
 * Tools to work with a permission type field.
 */
trait Permissionable
{
    /** @var array Associations between permission-id and PermissionAssociation entity. */
    private $_permissions = [];

    protected function loadPermissions()
    {
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

    public function getPermission(string $permissionId): Permission
    {
        $this->loadPermissions();

        return $this->_permissions[$permissionId];
    }

}
