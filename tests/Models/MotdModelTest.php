<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\Motd;
use LotGD\Core\Tests\ModelTestCase;

/**
 * Tests the management of Characters
 */
class MotdModelTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "motd";
    
    public function testFetching()
    {
        $em = $this->getEntityManager();
        
        $author = $em->getRepository(Character::class)->find(1);
        
        // Test normal message
        $motd1 = $em->getRepository(Motd::class)->find(1);
        $this->assertSame("This is the title", $motd1->getTitle());
        $this->assertSame("This is the body of the message", $motd1->getBody());
        $this->assertSame($author, $motd1->getAuthor());
        $this->assertSame($author, $motd1->getApparantAuthor());
        $this->assertFalse($motd1->getSystemMessage());
        
        // Test System message
        $motd2 = $em->getRepository(Motd::class)->find(2);
        $this->assertTrue($motd2->getSystemMessage());
        $this->assertNotSame($motd2->getAuthor(), $motd2->getApparantAuthor());
        $this->assertSame($author, $motd2->getAuthor());
        $this->assertNotSame("Deleted Character Name", $motd2->getAuthor()->getDisplayName());
        
        // Test message with unknown author
        $motd3 = $em->getRepository(Motd::class)->find(3);
        $this->assertSame("Deleted Testcharacter", $motd3->getAuthor()->getDisplayName());
    }
    
    public function testTimezone()
    {
        $em = $this->getEntityManager();
        
        $time1 = $em->getRepository(Motd::class)->find(1)->getCreationTime();
        $time2 = new \DateTime("2016-05-03 14:19:12");
        $time3 = $time2->setTimezone(new \DateTimeZone("Europe/Zurich"));
        $time4 = new \DateTime("2016-05-03 14:19:12");
        $time4->setTimezone(new \DateTimeZone("America/Los_Angeles"));
        
        $this->assertSame($time1->getTimestamp(), $time2->getTimestamp());
        $this->assertEquals($time1, $time2);
        $this->assertSame($time2, $time3);
        $this->assertEquals($time2->getTimezone(), $time3->getTimezone());
        $this->assertNotEquals($time1->getTimezone(), $time2->getTimezone());
        $this->assertSame($time1->getTimestamp(), $time3->getTimestamp());
        $this->assertNotEquals($time2->getTimezone(), $time4->getTimezone());
    }
    
    public function dataCreateSaveAndRetrieve(): array
    {
        return [
            [[
                "author" => 1,
                "title" => "ABC_\"EFG",
                "body" => "Lorem îpsum etc pp",
                "systemMessage" => false,
            ]],
            [[
                "author" => 1,
                "title" => "AnotherOne",
                "body" => "Test a Second One",
                "systemMessage" => true,
            ]],
        ];
    }
    
    /**
     * @dataProvider dataCreateSaveAndRetrieve
     */
    public function testCreateSaveAndRetrieve(array $motdCreationArguments)
    {
        $em = $this->getEntityManager();
        // Set Author to the correct author instance. Cannot be moved to the dataProvider.
        $motdCreationArguments["author"] = $em->getRepository(Character::class)->find($motdCreationArguments["author"]);
        
        $motd = Motd::create($motdCreationArguments);
        $motd->save($em);
        
        $id = $motd->getId();
        
        $em->flush();
        $em->clear();
        
        $checkMotd = $this->getEntityManager()->getRepository(Motd::class)->find($id);

        $this->assertSame($motdCreationArguments["author"]->getName(), $checkMotd->getAuthor()->getName());
        $this->assertSame($motdCreationArguments["title"], $checkMotd->getTitle());
        $this->assertSame($motdCreationArguments["body"], $checkMotd->getBody());
        $this->assertEquals($motd->getCreationTime(), $checkMotd->getCreationTime());
        
        if ($motdCreationArguments["systemMessage"] === true) {
            $this->assertNotSame($motdCreationArguments["author"]->getName(), $checkMotd->getApparantAuthor()->getName());
        } else {
            $this->assertSame($motdCreationArguments["author"]->getName(), $checkMotd->getApparantAuthor()->getName());
        }
        
        $em->flush();
    }
}
