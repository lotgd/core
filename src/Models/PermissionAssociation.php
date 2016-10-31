<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

/**
 * Extend this class to provide an association between an entity and a permission.
 */
abstract class PermissionAssociation
{
    /** @OneToOne(targetEntity="Permission", mappedBy="owner") */
    protected $permissionId;
    /** @Column(type="int") */
    protected $permissionState;
}
