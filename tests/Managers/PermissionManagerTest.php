<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Managers;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Game;
use LotGD\Core\PermissionManager;
use LotGD\Core\Exceptions\PermissionAlreadyExistsException;
use LotGD\Core\Exceptions\PermissionDoesNotExistException;
use LotGD\Core\Exceptions\PermissionIdNotFoundException;
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

        $this->assertTrue($user->hasPermissionSet("test/permission_one"));
        $this->assertTrue($user->hasPermissionSet("test/permission_two"));
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

    public function testIfAddingAnAlreadySetPermissionToAnUserResultsInException()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permission = $em->getRepository(Permission::class)->find("test/permission_two");

        $this->expectException(PermissionAlreadyExistsException::class);
        $user->addPermission($permission, PermissionManager::Denied);
    }

    public function testIfRemovingANotSetPermissionFromAnUserResultsInException()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permission = $em->getRepository(Permission::class)->find("test/permission_tri");

        $this->expectException(PermissionDoesNotExistException::class);
        $user->removePermission("test/permission_tri");
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

    public function testIfAllowingAnAllowedPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));
        $permissionManager->allow($user, "test/permission_one");
        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_one"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_one"));
    }

    public function testIfAllowingAnDeniedPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->isDenied($user, "test/permission_two"));
        $permissionManager->allow($user, "test/permission_two");
        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_two"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_two"));
    }

    public function testIfAllowingANonExistingPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));
        $permissionManager->allow($user, "test/permission_tri");
        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_tri"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_tri"));
    }

    public function testIfDenyingAnAllowedPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_one"));
        $permissionManager->deny($user, "test/permission_one");
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_one"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_one"));
    }

    public function testIfDenyingAnDeniedPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->isDenied($user, "test/permission_two"));
        $permissionManager->deny($user, "test/permission_two");
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_two"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_two"));
    }

    public function testIfDenyingANonExistingPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));
        $permissionManager->deny($user, "test/permission_tri");
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_tri"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertTrue($permissionManager->isDenied($user, "test/permission_tri"));
    }

    public function testIfRemovingAnAllowedPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->isAllowed($user, "test/permission_one"));
        $permissionManager->remove($user, "test/permission_one");
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_one"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_one"));
    }

    public function testIfRemovingADeniedPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertTrue($permissionManager->isDenied($user, "test/permission_two"));
        $permissionManager->remove($user, "test/permission_two");
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_two"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_two"));
    }

    public function testIfRemovingANonExistingPermissionWorks()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));
        $permissionManager->remove($user, "test/permission_tri");
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));

        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find(1);
        $this->assertFalse($permissionManager->hasPermissionSet($user, "test/permission_tri"));
    }

    public function testIfRequestingANonExistingPermissionThrowsAnException()
    {
        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find(1);
        $permissionManager = $this->getPermissionManager($em);

        $this->expectException(PermissionIdNotFoundException::class);

        $permissionManager->allow($user, "test/non_existing_permission");
    }

    public function testSomething()
    {
        $this->assertTrue(True);
    }
}
