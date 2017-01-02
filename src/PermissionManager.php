<?php
declare(strict_types=1);

namespace LotGD\Core;

use LotGD\Core\Exceptions\PermissionIdNotFoundException;
use LotGD\Core\Models\Actor;
use LotGD\Core\Models\PermissionableInterface;
use LotGD\Core\Models\Permission;

/**
 * The PermissionManager manages (checks and manipulates) of actors.
 *
 * The PermissionManager class provides methods to work with permissions and is
 * the only way to check and manipulate permissions. It can be used to create or
 * delete permissions, to remove, allow or deny permissions to actors and to
 * check whether an actor has a certain permission or if it is explicitely
 * denied from him.
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

    private $game;

    /**
     * Construct a permission manager.
     * @param Game $g The game.
     */
    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Checks if an actor has a permission set. No assumption can be made if it's allowed or denied.
     * @param \LotGD\Core\PermissionableInterface $actor
     * @param string $permissionId
     * @return bool True if the permission has been set, be it allowed or denied.
     */
    public function hasPermissionSet(
        Actor $actor,
        string $permissionId
    ): bool {
        if ($actor->hasPermissionSet($permissionId)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if an actor is allowed a given permission.
     * @param \LotGD\Core\PermissionableInterface $actor
     * @param string $permissionId
     * @return bool True if the actor has the permission set and it's state is allowed.
     */
    public function isAllowed(
        Actor $actor,
        string $permissionId
    ): bool {
        if ($actor->hasPermissionSet($permissionId)) {
            return $actor->getPermission($permissionId)->checkState(static::Allowed);
        } else {
            return false;
        }
    }

    /**
     * Checks if an actor is denied a given permission.
     * @param \LotGD\Core\PermissionableInterface $actor
     * @param string $permissionId
     * @return bool True if the actor has the permission set and it's state is denied.
     */
    public function isDenied(
        Actor $actor,
        string $permissionId
    ): bool {
        if ($actor->hasPermissionSet($permissionId)) {
            return $actor->getPermission($permissionId)->checkState(static::Denied);
        } else {
            return false;
        }
    }

    /**
     * Retrieves a permission entity from the database by a permission id.
     * @param string $permissionId
     * @return Permission
     * @throws PermissionIdNotFoundException
     */
    private function findPermission(string $permissionId): Permission
    {
        $em = $this->game->getEntityManager();
        $result = $em->getRepository(Permission::class)->find($permissionId);

        if ($result) {
            return $result;
        } else {
            throw new PermissionIdNotFoundException("Permission {$permissionId} was not found.");
        }
    }

    /**
     * Allows an actor a permission given by the permission id.
     * @param PermissionableInterface $actor
     * @param string $permissionId
     */
    public function allow(
        Actor $actor,
        string $permissionId
    ) {
        if ($actor->hasPermissionSet($permissionId)) {
            if ($this->isAllowed($actor, $permissionId) == false) {
                $permission = $actor->getPermission($permissionId);
                $permission->setState(static::Allowed);

                $name = $actor->getActorName();
                $this->game->getLogger()->debug("Granting permission {$permissionId} to {$name} (from denied).");
            }
        } else {
            $permission = $this->findPermission($permissionId);
            $actor->addPermission($permission, static::Allowed);

            $name = $actor->getActorName();
            $this->game->getLogger()->debug("Granting permission {$permissionId} to {$name} (from nothing).");
        }
    }

    /**
     * Denies an actor a permission given by the permission id.
     * @param PermissionableInterface $actor
     * @param string $permissionId
     */
    public function deny(
        Actor $actor,
        string $permissionId
    ) {
        if ($actor->hasPermissionSet($permissionId)) {
            if ($this->isDenied($actor, $permissionId) == false) {
                $permission = $actor->getPermission($permissionId);
                $permission->setState(static::Denied);

                $name = $actor->getActorName();
                $this->game->getLogger()->debug("Denying permission {$permissionId} from {$name} (from allowed).");
            }
        } else {
            $permission = $this->findPermission($permissionId);
            $actor->addPermission($permission, static::Denied);

            $name = $actor->getActorName();
            $this->game->getLogger()->debug("Denying permission {$permissionId} from {$name} (from nothing).");
        }
    }

    /**
     * Removes a permission from an actor.
     * @param PermissionableInterface $actor
     * @param string $permissionId
     */
    public function remove(
        Actor $actor,
        string $permissionId
    ) {
        if ($actor->hasPermissionSet($permissionId)) {
            $permissionAssoc = $actor->getPermission($permissionId);
            $actor->removePermission($permissionId);

            $name = $actor->getActorName();
            $this->game->getLogger()->debug("Removing permission {$permissionId} from {$name}).");
        }
    }
}
