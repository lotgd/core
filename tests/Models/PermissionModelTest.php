<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Permission;
use LotGD\Core\Tests\CoreModelTestCase;

/**
 * Tests the Permission model.
 */
class PermissionModelTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "permission";
    
    public function testIfPermissionsCanBeFetched()
    {
        $em = $this->getEntityManager();
        $permission = $em->getRepository(Permission::class)->find("lotgd/core/superuser");

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals("lotgd/core/superuser", $permission->getId());
        $this->assertEquals("lotgd/core", $permission->getLibrary());
        $this->assertEquals("Superuser. Superseeds all flags.", $permission->getName());
    }
    
    public function testIfPermissionsCanBeCreated()
    {
        $permission = Permission::create([
            "id" => "test/core/testpermission",
            "library" => "test/core",
            "name"=> "A permission for testing."
        ]);
        
        $this->assertInstanceOf(Permission::class, $permission);
        
        $em = $this->getEntityManager();
        $permission->save($em);
        $em->clear();
        
        $permission = $em->getRepository(Permission::class)->find("test/core/testpermission");
        
        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals("test/core/testpermission", $permission->getId());
        $this->assertEquals("test/core", $permission->getLibrary());
        $this->assertEquals("A permission for testing.", $permission->getName());
    }
    
    public function testIfIdCannotBeChanged()
    {
        $em = $this->getEntityManager();
        $permission = $em->getRepository(Permission::class)->find("lotgd/core/superuser");
        
        $this->expectException(ArgumentException::class);
        
        $permission->setId("another id.");
    }
}
