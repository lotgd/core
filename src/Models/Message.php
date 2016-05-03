<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use DateTime;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Exceptions\InvalidModelException;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Tools\Model\Deletor;
use LotGD\Core\Tools\Model\Saveable;

/**
 * Model for messages
 * @Entity
 * @Table(name="messages")
 */
class Message
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /**
     * @ManyToOne(targetEntity="Character", fetch="EAGER")
     * @JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     */
    private $author;
    /** @Column(type="text", nullable=false) */
    private $message;
    /** @ManyToOne(targetEntity="MessageThread", inversedBy="messages", fetch="EAGER") */
    private $thread;
    /** @Column(type="datetime", nullable=false) */
    private $createdAt;
    /** @Column(type="boolean", nullable=false) */
    private $systemMessage = false;
    
    /**
     * Sends a message to a MessageThread
     * @param \LotGD\Core\Models\Character $from
     * @param string $message
     * @param \LotGD\Core\Models\MessageThread $thread
     * @param bool $systemMessage
     * @return \LotGD\Core\Models\Message
     */
    public static function send(
        Character $from,
        string $message,
        MessageThread $thread,
        bool $systemMessage = false
    ) {
        $thread->addMessage(new self($from, $message, $thread, $systemMessage));
    }
    
    /**
     * Sends a system message to a MessageThread
     * @param string $message
     * @param \LotGD\Core\Models\MessageThread $thread
     * @return \LotGD\Core\Models\Message
     */
    public static function sendSystemMessage(
        string $message,
        MessageThread $thread
    ) {
        $thread->addMessage(new self(SystemCharacter::getInstance(), $message, $thread, true));
    }
    
    /**
     * Constructs the message.
     * 
     * This method has been made protected to prevent from accessing it directly. Use
     * the static methods self::send() and self::sendSystemMessage() instead.
     * @param \LotGD\Core\Models\Character $from
     * @param string $message
     * @param \LotGD\Core\Models\Thread $thread
     * @param bool $systemMessage
     * @throws ArgumentException
     */
    protected function __construct(CharacterInterface $from, string $message, MessageThread $thread, bool $systemMessage)
    {
        if ($from instanceof Character) {
            if ($from->isDeleted() === true) {
                throw new ArgumentException("A message cannot get written by a deleted character.");
            }
            $this->author = $from;
        } elseif ($systemMessage === false) {
            // This should not happen since the constructor is not a public method
            throw new ArgumentException(
                sprintf(
                    'If $from is not an instance of %s, $systemMessage must be true',
                    Character::class
                )
            );
        }
        
        $this->message = $message;
        $this->thread = $thread;
        $this->createdAt = new DateTime("now");
        $this->systemMessage = $systemMessage;
    }
    
    /**
     * Returns the id
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Returns the true character of the message
     * @return \LotGD\Core\Models\CharacterInterface
     */
    public function getAuthor(): CharacterInterface
    {
        if (is_null($this->author)) {
            return SystemCharacter::getInstance();
        }
        else {
            return $this->author;
        }
    }
    
    /**
     * Returns the apparant character of the message.
     * 
     * If a character sends a system message, this method will return the SystemCharacter message
     * instead of the true author.
     * @return \LotGD\Core\Models\CharacterInterface
     */
    public function getApparantAuthor(): CharacterInterface
    {
        if ($this->isSystemMessage()) {
            return SystemCharacter::getInstance();
        }
        else {
            return $this->getAuthor();
        }
    }
    
    /**
     * Returns the message
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Returns the thread this message belongs to
     * @return \LotGD\Core\Models\MessageThread
     */
    public function getThread(): MessageThread
    {
        return $this->thread;
    }
    
    /**
     * Sets the thread this message belongs to, once.
     * 
     * A message that belongs to a thread needs to stay there - there is no need for messages to
     * switch the thread and end up in a complete different discussion.
     * @param \LotGD\Core\Models\MessageThread $thread
     * @throws ParentAlreadySetException
     */
    public function setThread(MessageThread $thread)
    {
        if (is_null($this->thread) === false) {
            throw new ParentAlreadySetException("A message's thread cannot be changed.");
        }
        
        $this->thread = $thread;
    }
    
    /**
     * Returns the datetime this message was created at
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
    
    /**
     * Returns true if the message is a system message
     * @return bool
     */
    public function isSystemMessage(): bool
    {
        return $this->systemMessage;
    }
}
