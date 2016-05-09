<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="messages")
 */
class Message
{
    use Creator;
    use Deletor;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /**
     * @ManyToOne(targetEntity="Character", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="author_id", referencedColumnName="id", nullable=false)
     */
    private $author;
    /**
     * @ManyToOne(targetEntity="Character", cascade={"persist"}, fetch="EAGER")
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
        "author",
        "title",
        "body",
        "systemMessage",
    ];
    
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
        return $this->author;
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
    public function setAuthor(Character $author = null)
    {
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
     * @param \LotGD\Core\Models\Character $author
     */
    public function setAddressee(Character $author = null)
    {
        $this->addressee = $author;
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
}
