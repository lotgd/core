<?php

declare(strict_types=1);

namespace LotGD\Core\Models\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

use LotGD\Core\Models\MessageThread;

/**
 * Repository for MessageThreads.
 */
class MessageThreadRepository extends EntityRepository
{
    /**
     * Creates a thread key based on the participating characters and a schema.
     * @param array $listOfCharacters
     * @param string $messageSchema
     * @return string
     */
    public static function createThreadKey(array $listOfCharacters, string $messageSchema): string
    {
        // ToDo: Replace array with CharacterCollection
        \usort(
            $listOfCharacters,
            function ($a, $b) {
                return $a->getId() <=> $b->getId();
            }
        );
        
        $threadParticipants = "";
        foreach ($listOfCharacters as $character) {
            $threadParticipants .= $character->getId() . ".";
        }
        
        return $messageSchema . "://" . \md5($threadParticipants);
    }
    
    /**
     * Finds a messageThread.
     * @param array $listOfCharacters
     * @return MessageThread
     */
    public function findOrCreateFor(array $listOfCharacters): MessageThread
    {
        $threadKey = self::createThreadKey($listOfCharacters, "messageThread");
        
        try {
            $thread = $this->getEntityManager()->createQueryBuilder()
                ->select("e")
                ->from($this->getEntityName(), "e")
                ->where("e.threadKey = :threadKey")
                ->setParameter("threadKey", $threadKey)
                ->getQuery()
                ->getSingleResult()
            ;
            
            return $thread;
        } catch (NoResultException $e) {
            $newMessageThread = new MessageThread($threadKey, $listOfCharacters, false);
            $newMessageThread->save($this->getEntityManager());
            
            return $newMessageThread;
        }
    }
    
    /**
     * Finds a systemMessage or returns a newly created, read-only thread.
     * @param array $listOfCharacters
     * @return MessageThread
     */
    public function findOrCreateReadonlyFor(array $listOfCharacters): MessageThread
    {
        $threadKey = self::createThreadKey($listOfCharacters, "systemMessage");
        
        try {
            $thread = $this->getEntityManager()->createQueryBuilder()
                ->select("e")
                ->from($this->getEntityName(), "e")
                ->where("e.threadKey = :threadKey")
                ->setParameter("threadKey", $threadKey)
                ->getQuery()
                ->getSingleResult()
            ;
            
            return $thread;
        } catch (NoResultException $e) {
            $newMessageThread = new MessageThread($threadKey, $listOfCharacters, true);
            $newMessageThread->save($this->getEntityManager());
            
            return $newMessageThread;
        }
    }
}
