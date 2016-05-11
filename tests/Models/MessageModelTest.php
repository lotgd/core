<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\MessageThread;
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
    
    public function testSendMessageToSingleCharacter()
    {
        $em = $this->getEntityManager();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(4);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character1, $character2]);
        $thread2 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character2, $character1]);
        
        $this->assertSame($thread1, $thread2);
        
        Message::send($character1, "Hi, how are you?", $thread1);
        Message::send($character2, "I'm fine, and you?", $thread1);
        Message::send($character1, "Sorry, I need to leave~", $thread1);
        
        $this->assertSame(3, count($thread1->getMessages()));
        
        // Write changes to database and clear entity cache.
        $em->flush();
        $em->clear();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(4);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character1, $character2]);
        
        // $thread1 should come from the database
        $this->assertInternalType("int", $thread1->getId());
        
        $this->assertSame(3, count($thread1->getMessages()));
        $this->assertSame(2, count($character1->getMessageThreads()));
        $this->assertSame(1, count($character2->getMessageThreads()));
        $this->assertContains($thread1, $character1->getMessageThreads());
        $this->assertContains($thread1, $character2->getMessageThreads());
    }
    
    public function testSendMessageToMultipleCharacters()
    {
        $em = $this->getEntityManager();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        $character3 = $em->getRepository(Character::class)->find(3, CharacterRepository::INCLUDE_SOFTDELETED);
        $character4 = $em->getRepository(Character::class)->find(4);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character1, $character2, $character3, $character4]);
        $thread2 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character4, $character2, $character1, $character3]);
        $this->assertSame($thread1, $thread2);
        
        Message::send($character1, "Multi-User-Message", $thread1);
        Message::send($character2, "Multi-User-Message", $thread1);
        try {
            $exception = false;
            Message::send($character3, "Multi-User-Message", $thread1);
        } catch(\LotGD\Core\Exceptions\ArgumentException $e) {
            $exception = true;
        }
        Message::send($character4, "Multi-User-Message", $thread1);
        
        $this->assertTrue($exception);
        $this->assertSame(3, count($thread1->getMessages()));
        $this->assertSame(2, count($character1->getMessageThreads()));
        $this->assertSame(2, count($character2->getMessageThreads()));
        $this->assertSame(1, count($character3->getMessageThreads()));
        $this->assertSame(1, count($character4->getMessageThreads()));
        
        $em->flush();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        $character3 = $em->getRepository(Character::class)->find(3, CharacterRepository::INCLUDE_SOFTDELETED);
        $character4 = $em->getRepository(Character::class)->find(4);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character1, $character2, $character3, $character4]);
        
        // $thread1 should come from the database
        $this->assertInternalType("int", $thread1->getId());
        $this->assertSame(3, count($thread1->getMessages()));
        $this->assertSame(2, count($character1->getMessageThreads()));
        $this->assertSame(2, count($character2->getMessageThreads()));
        $this->assertSame(1, count($character3->getMessageThreads()));
        $this->assertSame(1, count($character4->getMessageThreads()));
    }
    
    public function testSendSystemMessageToSingleCharacter()
    {
        $em = $this->getEntityManager();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateReadonlyFor([$character1]);
        $thread2 = $em->getRepository(MessageThread::class)->findOrCreateReadonlyFor([$character2]);
        
        $this->assertNotSame($thread1, $thread2);
        
        Message::sendSystemMessage("This is a Systemmessage for Character 1.", $thread1);
        Message::sendSystemMessage("This is a Systemmessage for Character 2.", $thread2);
        
        $em->flush();
        $em->clear();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateReadonlyFor([$character1]);
        $thread2 = $em->getRepository(MessageThread::class)->findOrCreateReadonlyFor([$character2]);
        
        $this->assertSame(1, count($thread1->getMessages()));
        $this->assertSame(1, count($thread2->getMessages()));
        
        // Test the impossibility to answer to a system message thread, but another system message 
        // needs to be able to get attached
        try {
            $exception = false;
            Message::send($character1, "A normal message", $thread1);
        } catch (\LotGD\Core\Exceptions\CoreException $ex) {
            $exception = true;
        }
        
        Message::sendSystemMessage("A second system Message", $thread1);
        
        $this->assertTrue($exception);
        $this->assertSame(2, count($thread1->getMessages()));
    }
    
    public function testSendSystemMessageToMultipleCharacters()
    {
        $em = $this->getEntityManager();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateReadonlyFor([$character1, $character2]);
        $thread2 = $em->getRepository(MessageThread::class)->findOrCreateFor([$character1, $character2]);
        
        $this->assertNotSame($thread1, $thread2);
        
        Message::sendSystemMessage("A system message to 2 recipients", $thread1);
        
        $em->flush();
        $em->clear();
        
        $character1 = $em->getRepository(Character::class)->find(1);
        $character2 = $em->getRepository(Character::class)->find(2);
        
        $thread1 = $em->getRepository(MessageThread::class)->findOrCreateReadonlyFor([$character1, $character2]);
        
        $this->assertSame(1, count($thread1->getMessages()));
    }
}
