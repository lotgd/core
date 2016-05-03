<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="motd")
 */
class Motd
{
    use Creator;
    use Deletor;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** 
     * @ManyToOne(targetEntity="Character", cascade={"all"}, fetch="LAZY")
     * @JoinColumn(name="author_id", referencedColumnName="id", nullable=True)
     */
    private $author;
    /** @Column(type="string", length=255, nullable=false) */
    private $title;
    /** @Column(type="text", nullable=false) */
    private $body;
    /** @Column(type="datetime", nullable=false) */
    private $creationTime;
    
    /** @var array */
    private static $fillable = [
        "author",
        "title",
        "body"
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
     * @return \LotGD\Core\Models\Character
     */
    public function getAuthor(): Character
    {   
        if (is_null($this->author)) {
            return Character::create(["name" => "System"]);
        }
        else {
            return $this->author;
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
}
