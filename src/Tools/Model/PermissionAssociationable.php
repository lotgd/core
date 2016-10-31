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
     * @ManyToOne(targetEntity="LotGD\Core\Models\Permission", inversedBy="permission")
     * @JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected $permission;
    /** @Column(type="integer") */
    protected $permissionState;
}
