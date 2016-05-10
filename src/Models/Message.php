<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Exceptions\InvalidModelException;
use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

/**
 * Model for messages between 2 characters or for system messages to characters.
 *
 * This entity is not configured to persist - meaning that this->save has to be
 * called explicitly in order to access the message from the author's and addressee's
 * collections.
 * @Entity
 * @Table(name="messages")
 */
class Message implements CreateableInterface
{
    use Creator;
    use Deletor;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /**
     * @ManyToOne(targetEntity="Character", inversedBy="sentMessages", fetch="EAGER")
     * @JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     */
    private $author;
    /**
     * @ManyToOne(targetEntity="Character", inversedBy="receivedMessages", fetch="EAGER")
     * @JoinColumn(name="addressee_id", referencedColumnName="id", nullable=false)
     */
    private $addressee;
    /** @Column(type="string", length=255, nullable=false) */
    private $title;
    /** @Column(type="text", nullable=false) */
    private $body;
    /** @Column(type="datetime", nullable=false) */
    private $creationTime;
    /** @Column(type="boolean", nullable=false) */
    private $hasBeenRead = false;
    /** @Column(type="boolean", nullable=false) */
    private $systemMessage = false;
    
    /** @var array */
    private static $fillable = [
        "title",
        "body",
    ];
    
    /**
     * Creates a message with author, addressee, title and body and returns it. 
     * @param \LotGD\Core\Models\Character $from
     * @param \LotGD\Core\Models\Character $to
     * @param string $title
     * @param string $body
     * @return \LotGD\Core\Models\Message
     */
    public static function send(Character $from, Character $to, string $title, string $body): Message
    {
        $newMessage = self::create([
            "title" => $title,
            "body" => $body
        ]);
        
        $newMessage->setAuthor($from);
        $newMessage->setAddressee($to);
        
        return $newMessage;
    }
    
    /**
     * Creates a system message with addressee, title and body and returns it. 
     * @param \LotGD\Core\Models\Character $from
     * @param \LotGD\Core\Models\Character $to
     * @param string $title
     * @param string $body
     * @return \LotGD\Core\Models\Message
     */
    public static function sendSystemMessage(Charactter $to, string $title, string $body): Message
    {
        $newMessage = self::create([
            "title" => $title,
            "body" => $body,
        ]);
        
        $newMessage->setAddressee($to);
        $newMessage->setSystemMessage(true);
        
        return $newMessage;
    }
    
    /**
     * Constructs an entity and sets default datetime to now.
     */
    public function __construct()
    {
        $this->creationTime = new \DateTime("now");
    }
    
    /**
     * Returns the entities ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Returns the character who wrote this motd
     *
     * Returns always the real author of the message, even if it is a
     * system message. Use $this->getSystemMessage() to check if it is a system
     * message or $this->getAppearentAuthor() to get the appearent author.
     * @return \LotGD\Core\Models\Character
     */
    public function getAuthor(): CharacterInterface
    {
        if (is_null($this->author)) {
            return new MissingCharacter();
        }
        else {
            return $this->author;
        }
    }
    
    /**
     * Returns the appearent author of this message.
     * @return \LotGD\Core\Models\CharacterInterface
     */
    public function getApparantAuthor(): CharacterInterface
    {
        if ($this->getSystemMessage() === true) {
            return SystemCharacter::getInstance();
        } else {
            return $this->getAuthor();
        }
    }
    
    /**
     * Sets the author of this motd
     * @param \LotGD\Core\Models\Character $author
     */
    public function setAuthor(Character $author)
    {
        if ($this->author !== null) {
            throw new ParentAlreadySetException("A message's author cannot be changed.");
        }
        
        $this->author = $author;
    }
    
    /**
     * Returns the character who has received this message.
     * @return \LotGD\Core\Models\Character
     */
    public function getAddressee(): CharacterInterface
    {
        return $this->addressee;
    }
    
    /**
     * Sets the author of this motd
     * @param \LotGD\Core\Models\Character $addressee
     */
    public function setAddressee(Character $addressee)
    {
        if ($this->addressee !== null) {
            throw new ParentAlreadySetException("A message cannot be moved.");
        }
        
        $this->addressee = $addressee;
    }
    
    /**
     * Returns the title of the message
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    
    /**
     * Sets the title of the message
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }
    
    /**
     * Returns the body of the message
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }
    
    /**
     * Sets the body of the message
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->body = $body;
    }
    
    /**
     * Returns the creation time. Modification of this has no effect.
     * @return \DateTime
     */
    public function getCreationTime(): \DateTime
    {
        return $this->creationTime;
    }
    
    /**
     * Sets the creation time. Needs to be set to a new datetime instance.
     * @param \DateTime $creationTime
     */
    public function setCreationTime(\DateTime $creationTime)
    {
        $this->creationTime = $creationTime;
    }
    
    /**
     * Returns true if the addressee has the message read already.
     * @return bool
     */
    public function hasBeenRead(): bool
    {
        return $this->hasBeenRead;
    }
    
    /**
     * Sets the state of the message
     * @param bool $hasBeenRead
     */
    public function setHasBeenRead(bool $hasBeenRead)
    {
        $this->hasBeenRead = $hasBeenRead;
    }
    
    /**
     * Returns true if the motd is a system message
     * @return bool
     */
    public function getSystemMessage(): bool
    {
        return $this->systemMessage;
    }
    
    /**
     * Set to true of the message should be a system message
     * @param bool $isSystemMessage
     */
    public function setSystemMessage(bool $isSystemMessage = true)
    {
        $this->systemMessage = $isSystemMessage;
    }
    
    /**
     * Checks model validity and persists it (essentially sending it).
     * @param EntityManagerInterface $em
     * @throws InvalidModelException
     */
    public function save(EntityManagerInterface $em)
    {
        if ($this->addressee === null) {
            throw new InvalidModelException("The Addressee of Message model must not be null.");
        }
        
        if ($this->author === null && $this->getSystemMessage() === false) {
            throw new InvalidModelException("Author of Message model cannot be empty without beeing a system message.");
        }
        
        // Add manually to received and set messages list
        $this->getAddressee()->listReceivedMessages()->add($this);
        $this->getAuthor()->listSentMessages()->add($this);
        
        // Persist and flush
        self::_save($this, $em);
    }
}
