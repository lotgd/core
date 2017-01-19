<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Generator;

use LotGD\Core\Exceptions\PermissionAlreadyExistsException;
use LotGD\Core\Exceptions\PermissionDoesNotExistException;
use LotGD\Core\Models\Permission;
use LotGD\Core\Models\PermissionAssociationInterface;

/**
 * This abtract class provides functionality for user entities that crates might
 * want to implement, such as permissions.
 */
abstract class Actor
{
    /** @var array Associations between permission-id and PermissionAssociation entity. */
    private $permissionIdToAssociation = [];

    /**
     * Needs to return a generator which iterates through all permission associations.
     * @return Generator List of PermissionAssociations.
     */
    abstract protected function getPermissionAssociations(): Generator;

    /**
     * Returns the class of the permission associations used for the entity
     * implementing this class.
     * @return string fully qualified class name of the permission association entity.
     */
    abstract protected function getPermissionAssociationClass(): string;

    /**
     * Loads all associated permissions for this actor.
     * @throws PermissionAssociationEntityMissingException
     */
    protected function loadPermissions()
    {
        if (class_exists($this->getPermissionAssociationClass()) === false) {
            throw new PermissionAssociationEntityMissingException(
                "The method getPermissionAssociationClass does not return a valid class name."
            );
        }

        if (empty($this->permissionIdToAssociation)) {
            foreach ($this->getPermissionAssociations() as $permission) {
                $this->permissionIdToAssociation[$permission->getId()] = $permission;
            }
        }
    }

    /**
     * Checks if the actor is associated with a given permission. For permission
     * checking, use only the PermissionManager class.
     * @param string $permissionId
     * @return bool
     */
    public function hasPermissionSet(string $permissionId): bool
    {
        $this->loadPermissions();

        return isset($this->permissionIdToAssociation[$permissionId]);
    }

    /**
     * Returns the associated permission given by an id. For permission
     * checking, use only the PermissionManager class.
     * @param string $permissionId
     * @return PermissionAssociationInterface
     */
    public function getPermission(string $permissionId): PermissionAssociationInterface
    {
        $this->loadPermissions();

        return $this->permissionIdToAssociation[$permissionId];
    }

    /**
     * Returns the raw permission given by the id. For permission
     * checking, use only the PermissionManager class.
     * @param string $permissionId
     * @return Permission
     */
    public function getRawPermission(string $permissionId): Permission
    {
        $this->loadPermissions();

        return $this->permissionIdToAssociation[$permissionId]->getPermission();
    }

    /**
     * Associates a permission with this actor in a given state.  For permission
     * manipulation, use only the PermissionManager class.
     * @param Permission $permission
     * @param int $state
     * @throws PermissionAlreadyExistsException
     */
    public function addPermission(Permission $permission, int $state)
    {
        $this->loadPermissions();

        if ($this->hasPermissionSet($permission->getId())) {
            $permissionId = $permission->getId();
            throw new PermissionAlreadyExistsException("The permission with the id {$permissionId} has already been set on this actor.");
        } else {
            $associationEntity = $this->getPermissionAssociationClass();

            $permissionAssoc = new $associationEntity($this, $permission, $state);
            $this->permissions->add($permissionAssoc);
            $this->permissionIdToAssociation[$permissionAssoc->getId()] = $permissionAssoc;
        }
    }

    /**
     * Removes an associated permission from this actor by a given id. For permission
     * manipulation, use only the PermissionManager class.
     * @param string $permissionId
     * @throws PermissionDoesNotExistException
     */
    public function removePermission(string $permissionId)
    {
        $this->loadPermissions();

        if ($this->hasPermissionSet($permissionId)) {
            $permissionAssoc = $this->getPermission($permissionId);
            $this->permissions->removeElement($permissionAssoc);
            unset($this->permissionIdToAssociation[$permissionId]);
        } else {
            throw new PermissionDoesNotExistException("The permission with the id {$permissionId} has not been set on this actor.");
        }
    }
}
