<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Exceptions\CoreException;
use LotGD\Core\Tools\Model\Saveable;

/**
 * A Thread of messages.
 *
 * @Entity(repositoryClass="LotGD\Core\Models\Repositories\MessageThreadRepository")
 * @Table(name="message_threads")
 */
class MessageThread implements SaveableInterface
{
    use Saveable;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=255, unique=true) */
    private $threadKey;
    /** @Column(type="boolean", options={"default"= false}) */
    private $readonly = false;
    /** @ManyToMany(targetEntity="Character", cascade={"persist"}, mappedBy="messageThreads")  */
    private $participants;
    /** @OneToMany(targetEntity="Message", mappedBy="thread", cascade={"persist"}) */
    private $messages;
    
    /**
     * Constructor. Sets the (unique) threadKey.
     * @param string $threadKey
     * @param array $participants
     * @param bool $readonly
     */
    public function __construct(string $threadKey, array $participants, bool $readonly = false)
    {
        $this->threadKey = $threadKey;
        $this->readonly = $readonly;
        
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
        
        foreach ($participants as $participant) {
            $this->participants->add($participant);
        }
    }
    
    /**
     * Returns the primary id of this message.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Returns a list of messages inside this thread.
     * @return Collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }
    
    /**
     *
     * @param \LotGD\Core\Models\Message $message
     * @throws CoreException
     */
    public function addMessage(Message $message)
    {
        if ($this->isReadonly() && $message->getApparantAuthor() instanceof SystemCharacter === false) {
            throw new CoreException("Cannot write a normal message to a readonly thread");
        }
        $this->messages->add($message);
    }
    
    /**
     * Get a collection of participants in this thread.
     * @return Collection
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }
    
    /**
     * Returns true if the thread is "readonly".
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->readonly;
    }
    
    /**
     * Persists the MessageThread and adds itself to the participants.
     * @param EntityManagerInterface $em
     */
    public function save(EntityManagerInterface $em)
    {
        foreach ($this->participants as $participant) {
            $participantsMessageThreads = $participant->getMessageThreads();
            if ($participantsMessageThreads->contains($this) === false) {
                $participantsMessageThreads->add($this);
            }
        }
        
        $em->persist($this);
        $em->flush();
    }
}
