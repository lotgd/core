<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Managers;

use LotGD\Core\Models\PermissionableInterface;
use LotGD\Core\Models\PermissionAssociation;
use LotGD\Core\Tools\Model\Permissionable;

use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\Ressources\Models\User;
use LotGD\Core\Tests\Ressources\Models\UserPermissionAssociation;

/**
 * Description of PermissionManagerTest
 */
class PermissionManagerTest extends CoreModelTestCase
{
    protected $dataset = "permission-manager";

    public function testSomething()
    {

        $this->assertTrue(True);
    }
}
