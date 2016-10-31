<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Managers;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Game;
use LotGD\Core\PermissionManager;
use LotGD\Core\Models\Permission;
use LotGD\Core\Models\PermissionableInterface;
use LotGD\Core\Models\PermissionAssociationInterface;
use LotGD\Core\Tools\Model\Permissionable;

use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\Ressources\TestModels\User;
use LotGD\Core\Tests\Ressources\TestModels\UserPermissionAssociation;

/**
 * Description of PermissionManagerTest
 */
class PermissionManagerTest extends CoreModelTestCase
{
    protected $dataset = "permission-manager";

    public function getPermissionManager(EntityManagerInterface $em): PermissionManager
    {
        $this->game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $this->game->method('getEntityManager')->willReturn($em);

        return new PermissionManager($this->game);
    }

    public function testUserHasPermission()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);

        $this->assertTrue($user->hasPermission("test/permission_one"));
        $this->assertTrue($user->hasPermission("test/permission_two"));
    }

    public function testUserReturnsPermission()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);

        $permission = $user->getRawPermission("test/permission_one");
        $this->assertInstanceOf(Permission::class, $permission);
    }

    public function testUserReturnsPermissionAssociation()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);

        $permission = $user->getPermission("test/permission_one");
        $this->assertInstanceOf(UserPermissionAssociation::class, $permission);
    }

    public function testIfHasPermissionSetWorksAsExpected()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->hasPermissionSet($user, "test/permission_one"));
        $this->assertTrue($permissionManager->hasPermissionSet($user, "test/permission_two"));
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_none"));
    }

    public function testIfIsAllowedSetWorksAsExpected()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_one"));
        $this->assertFalse($permissionManager->isAllowed($user, "test/permission_two"));
        $this->assertFalse($permissionManager->isAllowed($user, "test/permission_tri"));
        $this->assertFalse($permissionManager->isAllowed($user, "test/permission_none"));
    }

    public function testIfIsDeniedSetWorksAsExpected()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertFalse($permissionManager->isDenied($user, "test/permission_one"));
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_two"));
        $this->assertFalse($permissionManager->isDenied($user, "test/permission_tri"));
        $this->assertFalse($permissionManager->isDenied($user, "test/permission_none"));
    }

    public function testSomething()
    {
        $this->assertTrue(True);
    }
}
