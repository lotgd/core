<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\Message;
use LotGD\Core\Models\Repositories\CharacterRepository;
use LotGD\Core\Tests\ModelTestCase;

/**
 * Tests the management of Characters
 */
class MessageModelTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "messages";
    
    public function testFetching()
    {
        $em = $this->getEntityManager();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        $character3 = $em->getRepository(Character::class)->find(3, CharacterRepository::INCLUDE_SOFTDELETED);
        
        $message1 = $em->getRepository(Message::class)->find(1);
        $this->assertSame("This is the title", $message1->getTitle());
        $this->assertSame("This is the body of the message", $message1->getBody());
        $this->assertSame($character1, $message1->getAuthor());
        $this->assertSame($character1, $message1->getApparantAuthor());
        $this->assertSame($character2, $message1->getAddressee());
        $this->assertSame(true, $message1->hasBeenRead());
        
        
        $message2 = $em->getRepository(Message::class)->find(2);
        $this->assertSame("This is an unread message", $message2->getTitle());
        $this->assertSame("This is the body of the unread message", $message2->getBody());
        $this->assertSame($character1, $message2->getAuthor());
        $this->assertSame($character1, $message2->getApparantAuthor());
        $this->assertSame($character2, $message2->getAddressee());
        $this->assertSame(false, $message2->hasBeenRead());
        
        $message3 = $em->getRepository(Message::class)->find(3);
        $this->assertSame("This is an old message.", $message3->getTitle());
        $this->assertSame("This is an old message.", $message3->getBody());
        $this->assertSame($character3, $message3->getAuthor());
        $this->assertSame($character3, $message3->getApparantAuthor());
        $this->assertSame($character1, $message3->getAddressee());
        $this->assertSame(true, $message3->hasBeenRead());
    }
    
    public function testSendAndReceiving()
    {
        $em = $this->getEntityManager();
        
        // Create a message and store it in the database.
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $message = Message::send($character2, $character1, "Hello testSendAndReceiving", "Message 1.");
        $message->save($em);
        
        $em->clear();
        
        // Create a message and persist it, but don't store it in the database.
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $message = Message::send($character2, $character1, "Hello testSendAndReceiving", "Message 2.");
        $message->save($em);
        
        // Check sent messages
        $messagesSent = $em->getRepository(Character::class)->find(2)->listSentMessages();
        $found = 0;
        foreach ($messagesSent as $message) {
            if ($message->getAuthor()->getId() === 2
                && $message->getTitle() === "Hello testSendAndReceiving"
            ) {
                $found++;
            }
        }
        $this->assertSame(2, $found);
        
        // Check received messages
        $messagesReceived = $em->getRepository(Character::class)->find(1)->listReceivedMessages();
        $found = 0;
        foreach ($messagesReceived as $message) {
            if ($message->getAddressee()->getId() === 1
                && $message->getTitle() === "Hello testSendAndReceiving"
            ) {
                $found++;
            }
        }
        $this->assertSame(2, $found);
        
        // Create the message a third time and persist it.
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $message = Message::send($character2, $character1, "Hello testSendAndReceiving", "Message 3");
        $message->save($em);
        
        // Check sent messages again, now there should be 3 entries.
        $messagesSent = $em->getRepository(Character::class)->find(2)->listSentMessages();
        $found = 0;
        foreach ($messagesSent as $message) {
            if ($message->getAuthor()->getId() === 2
                && $message->getTitle() === "Hello testSendAndReceiving"
            ) {
                $found++;
            }
        }
        $this->assertSame(3, $found);
        
        // Check received messages again, now there should be 3 entries.
        $messagesReceived = $em->getRepository(Character::class)->find(1)->listReceivedMessages();
        $found = 0;
        foreach ($messagesReceived as $message) {
            if ($message->getAddressee()->getId() === 1
                && $message->getTitle() === "Hello testSendAndReceiving"
            ) {
                $found++;
            }
        }
        $this->assertSame(3, $found);
    }
    
    /**
     * @expectedException \LotGD\Core\Exceptions\InvalidModelException
     * @expectedExceptionMessage The Addressee of Message model must not be null.
     */
    public function testEmptyAddresseeException()
    {
        $newMessage = Message::create([
            "title" => "Hello",
            "body" => "Hello World.",
        ]);
        
        $newMessage->save($this->getEntityManager());
    }
    
    /**
     * @expectedException \LotGD\Core\Exceptions\InvalidModelException
     * @expectedExceptionMessage Author of Message model cannot be empty without beeing a system message.
     */
    public function testEmptyAuthorException()
    {
        
        $em = $this->getEntityManager();
        
        $newMessage = Message::create([
            "title" => "Hello",
            "body" => "Hello World.",
        ]);
        
        $newMessage->setAddressee($em->getRepository(Character::class)->find(1));
        
        $newMessage->save($em);
    }
    
    public function testTimezone()
    {
        $em = $this->getEntityManager();
        
        $time1 = $em->getRepository(Message::class)->find(1)->getCreationTime();
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
    
    /*public function dataCreateSaveAndRetrieve(): array
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
    }*/
    
    /**
     * @dataProvider dataCreateSaveAndRetrieve
     */
    /*public function testCreateSaveAndRetrieve(array $motdCreationArguments)
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
    }*/
}
