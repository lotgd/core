<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * Permissions can be managed with the PermissionManager.
 * 
 * The PermissionManager class provides methods to work with permissions. It can
 * be used to create or delete permissions, to remove, allow or deny permissions
 * to actors and to check whether an actor has a certain permission or if it is
 * explicitely denied from him.
 * 
 * The wording used in this class is:
 *  - allowed, the actor has a certain permission in the allowed state.
 *  - denied, the actor has a certain permission in the denied state.
 * 
 * To make this more clear, the following table summarizes how different methods
 * react.
 * 
 * Method
 *             State: | Unset | Allowed | Denied
 * -------------------+-------+---------+---------
 * isAllowed          | False | True    | False
 * isDenied           | False | False   | True
 * hasPermissionSet   | False | True    | True
 */
class PermissionManager
{
    const Allowed = 1;
    const Denied = -1;
    
    const Superuser = "lotgd/core/superuser";
    const AddScenes = "lotgd/core/scene/add";
    const EditScenes = "lotgd/core/scene/edit";
    const DeleteScenes = "lotgd/core/scene/delete";
    const AddCharacters = "lotgd/core/characters/add";
    const EditCharacters = "lotgd/core/characters/edit";
    const DeleteCharacters = "lotgd/core/characters/delete";
    
    /**
     * Checks if an actor has a permission set. No assumption can be made if it's allowed or denied.
     * @param \LotGD\Core\PermissionableInterface $actor
     * @param string $permissionId
     * @return bool True if the permission has been set, be it allowed or denied.
     */
    public function hasPermissionSet(
        PermissionableInterface $actor, 
        string $permissionId
    ): bool {
        
    }
    
    /**
     * Checks if an actor is allowed a given permission.
     * @param \LotGD\Core\PermissionableInterface $actor
     * @param string $permissionId
     * @return bool True if the actor has the permission set and it's state is allowed.
     */
    public function isAllowed(
        PermissionableInterface $actor, 
        string $permissionId
    ): bool {
        
    }
    
    /**
     * Checks if an actor is denied a given permission.
     * @param \LotGD\Core\PermissionableInterface $actor
     * @param string $permissionId
     * @return bool True if the actor has the permission set and it's state is denied.
     */
    public function isDenied(
        PermissionableInterface $actor, 
        string $permissionId
    ): bool {
        
    }
}
